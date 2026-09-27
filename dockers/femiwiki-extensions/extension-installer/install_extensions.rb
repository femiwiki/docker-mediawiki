# frozen_string_literal: true

require 'fileutils'
require 'tempfile'
require 'json'
require 'parallel'

# Temporary directory path for downloading
TEMP_DIRECTORY_PATH = '/tmp'
# Target directory path for extensions and skins
DESTINATION_PATH = '/mediawiki'

extensions_data = JSON.parse(File.read("#{__dir__}/extensions.json"))
# WMF extensions and skins, each pinned to a commit of this branch
WMF_BRANCH = extensions_data['WMF-branch']
WMF_extensions = extensions_data['WMF-extensions']
WMF_skins = extensions_data['WMF-skins']
SUBMODULE_MIRRORS = extensions_data.fetch('submodule-mirrors', {})
# non-WMF extensions and skins
non_WMF_all = extensions_data['non-WMF']
is_skin = ->(_k, v) { v.key?('type') and v['type'] == 'skin' }
non_WMF_extensions = non_WMF_all.reject(&is_skin)
non_WMF_skins = non_WMF_all.select(&is_skin)

puts 'Started installing extensions'

# A source that lacks a repository must fail, not wait for a password
ENV['GIT_TERMINAL_PROMPT'] = '0'

# A WMF extension or skin at its pinned commit, with its submodules, from the
# GitHub mirror or, when the mirror lacks the commit, from Gerrit
def fetch_wmf(name, type, sha)
  dir = "#{DESTINATION_PATH}/#{type}s/#{name}"
  sources = [
    "https://github.com/wikimedia/mediawiki-#{type}s-#{name}",
    "https://gerrit.wikimedia.org/r/mediawiki/#{type}s/#{name}"
  ]
  FileUtils.mkdir_p dir
  system('git', 'init', '-q', dir, exception: true)
  source = sources.find do |url|
    system('git', '-C', dir, 'fetch', '-q', '--depth', '1', url, sha)
  end
  raise "#{type}s/#{name}: #{sha} is on neither #{sources.join(' nor ')}" unless source

  puts "#{type}s/#{name} #{sha} from #{source}"
  # A relative submodule URL resolves against origin
  system('git', '-C', dir, 'remote', 'add', 'origin', source, exception: true)
  system('git', '-C', dir, '-c', 'advice.detachedHead=false', 'checkout', '-q', sha, exception: true)
  # Phabricator refuses to serve a commit by its hash, so its submodules come
  # from the GitHub repositories they mirror; other hosts may still refuse a
  # shallow fetch of a commit no ref points at
  mirrors = SUBMODULE_MIRRORS.flat_map { |from, to| ['-c', "url.#{to}.insteadOf=#{from}"] }
  submodules = ['git', *mirrors, '-C', dir, 'submodule', 'update', '-q', '--init', '--recursive']
  system(*submodules, '--depth', '1') or system(*submodules, exception: true)
  write_git_info(dir, name, type, sha)
  composer_install(dir)
  # Composer installs a package from source when it has no dist, .git included
  Dir.glob("#{dir}/**/.git", File::FNM_DOTMATCH).each { |git| FileUtils.rm_rf git }
end

# The two files extdist adds, which Special:Version reads in place of .git
def write_git_info(dir, name, type, sha)
  time = IO.popen(['git', '-C', dir, 'log', '-1', '--format=%ct', sha], &:read).strip
  File.write("#{dir}/gitinfo.json", JSON.generate(
    head: "#{sha}\n", headSHA1: "#{sha}\n", headCommitDate: time, branch: "#{sha}\n",
    remoteURL: "https://gerrit.wikimedia.org/r/mediawiki/#{type}s/#{name}"
  ))
  File.write("#{dir}/version", "#{name}: #{WMF_BRANCH}\n#{Time.at(time.to_i).utc.strftime('%FT%T')}\n\n#{sha[0, 7]}\n")
end

# The dependencies extdist bundles: it runs composer for any extension whose
# composer.json requires something, a PHP extension alone included
def composer_install(dir)
  composer = "#{dir}/composer.json"
  return unless File.exist?(composer)
  return if JSON.parse(File.read(composer)).fetch('require', {}).empty?

  system('composer', 'install', '--no-dev', '--ignore-platform-reqs', '--no-interaction', '--quiet',
         '--working-dir', dir, exception: true)
end

# There is maybe a rate limit on the sources, so keep the pool small
Parallel.each(WMF_extensions.to_a, in_threads: 4) { |name, sha| fetch_wmf(name, 'extension', sha) }
Parallel.each(WMF_skins.to_a, in_threads: 4) { |name, sha| fetch_wmf(name, 'skin', sha) }

# Make directories for each non-WMF extensions and skins
non_WMF_extensions.each_key { |extension| FileUtils.mkdir_p "#{DESTINATION_PATH}/extensions/#{extension}" }
non_WMF_skins.each_key { |skin| FileUtils.mkdir_p "#{DESTINATION_PATH}/skins/#{skin}" }

# Create a file that can be used by aria2c with the '--input-file=' option
#
# Reference:
#   https://aria2.github.io/manual/en/html/aria2c.html#id2
input_file = Tempfile.new
input_file.write(
  non_WMF_all.map do |name, data|
    url = data['template'].dup
    url.gsub!('$1', data['version']) if data.key?('version')
    url + "\n out=#{name}.tar.gz\n"
  end.join
)
input_file.close

# Execute aria2
puts 'Starting download'
system('aria2c', "--input-file=#{input_file.path}", "--dir=#{TEMP_DIRECTORY_PATH}", exception: true)
puts 'Finished download'

# Uncompress tar.gz files
Parallel.each(non_WMF_extensions.keys) do |extension|
  system('tar', '-xzf', "#{TEMP_DIRECTORY_PATH}/#{extension}.tar.gz", '--strip-components=1',
         '--directory', "#{DESTINATION_PATH}/extensions/#{extension}", exception: true)
end
Parallel.each(non_WMF_skins.keys) do |skin|
  system('tar', '-xzf', "#{TEMP_DIRECTORY_PATH}/#{skin}.tar.gz", '--strip-components=1',
         '--directory', "#{DESTINATION_PATH}/skins/#{skin}", exception: true)
end

puts 'Finished extension installing'
