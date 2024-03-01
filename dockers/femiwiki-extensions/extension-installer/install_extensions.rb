# frozen_string_literal: true

require 'fileutils'
require 'tempfile'
require 'net/http'
require 'json'
require 'parallel'

# Get configurations from command line options
if ARGV.empty?
  warn '어느 미디어위키 브랜치에서 다운받을 것인지를 입력해 주세요. 예: "REL1_34"'
  exit 1
end
MEDIAWIKI_BRANCH = ARGV[0]

# Temporary directory path for downloading
TEMP_DIRECTORY_PATH = '/tmp'
# Target directory path for extensions and skins
DESTINATION_PATH = '/mediawiki'

extensions_data = JSON.parse(File.read("#{__dir__}/extensions.json"))
# WMF extensions and skins
WMF_extensions = extensions_data["WMF-extensions"]
WMF_skins = extensions_data["WMF-skins"]
# non-WMF extensions and skins
non_WMF_all = extensions_data["non-WMF"]
is_skin = -> k, v { v.key?('type') and v['type'] == 'skin' }
non_WMF_extensions = non_WMF_all.reject(&is_skin)
non_WMF_skins = non_WMF_all.select(&is_skin)

# Names of the all extensions and skins
extensions_all = (
  WMF_extensions +
  non_WMF_extensions.keys
)
skins_all = (
  WMF_skins +
  non_WMF_skins.keys
)

puts 'Started installing extensions'

# Make directories for each extensions and skins
Parallel.each(extensions_all) do |extension|
  FileUtils.mkdir_p "#{DESTINATION_PATH}/extensions/#{extension}"
end
Parallel.each(skins_all) do |skin|
  FileUtils.mkdir_p "#{DESTINATION_PATH}/skins/#{skin}"
end

# Create a file that can be used by aria2c with the '--input-file=' option
#
# Reference:
#   https://aria2.github.io/manual/en/html/aria2c.html#id2
input_file = Tempfile.new

def name_to_aria2_input_line(name, type)
  branch_info_url = "https://gerrit.wikimedia.org/r/projects/mediawiki%2F#{type}s%2F#{name}/branches/#{MEDIAWIKI_BRANCH}"
  puts "Fetching the infomation of #{name} from #{branch_info_url}"
  response = Net::HTTP.get(URI(branch_info_url))
  # Response starts with a magic prefix line ")]}'\n", so strip it.
  # See:
  #   https://gerrit-review.googlesource.com/Documentation/rest-api.html#output
  response = response[5..-1]
  sha = JSON.parse(response)['revision']
  "https://extdist.wmflabs.org/dist/#{type}s/#{name}-#{MEDIAWIKI_BRANCH}-#{sha[0..6]}.tar.gz\n out=#{name}.tar.gz\n"
rescue JSON::ParserError => e
  puts "#{e.message} #{type}s/#{name}"
  raise e
end

input_file.write(
  # There is maybe a rate limit on gerrit, so explicitly use connection pool.
  Parallel.map(WMF_extensions, in_threads: 2) do |extension|
    name_to_aria2_input_line(extension, 'extension')
  end.join +
  Parallel.map(WMF_skins) do |skin|
    name_to_aria2_input_line(skin, 'skin')
  end.join +
  non_WMF_all.map do |name, data|
    url = data["template"]
    if data.key?('version')
      url.gsub!('$1', data['version'])
    end
    url + "\n out=#{name}.tar.gz\n"
  end.join
)
input_file.close

# Execute aria2
puts 'Starting download'
`aria2c --input-file=#{input_file.path} --dir=#{TEMP_DIRECTORY_PATH}`
puts 'Finished download'

# Uncompress tar.gz files
Parallel.each(extensions_all) do |extension|
  `tar -xzf '#{TEMP_DIRECTORY_PATH}/#{extension}.tar.gz' --strip-components=1 --directory '#{DESTINATION_PATH}/extensions/#{extension}'`
end
Parallel.each(skins_all) do |skin|
  `tar -xzf '#{TEMP_DIRECTORY_PATH}/#{skin}.tar.gz' --strip-components=1 --directory '#{DESTINATION_PATH}/skins/#{skin}'`
end

puts 'Finished extension intalling'
