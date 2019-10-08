# frozen_string_literal: true
require 'fileutils'
require 'tempfile'
require 'net/http'
require 'json'
require 'parallel'

# Get configurations from command line options
if ARGV.length == 0
  STDERR.puts '어느 미디어위키 브랜치에서 다운받을것인지를 입력해주세요. 예: "REL1_32"'
  exit 1
end
MEDIAWIKI_BRANCH = ARGV[0]

# Temporary directory path for Composer
COMPOSER_HOME_PATH = '/tmp/composer'
# Temporary directory path for downloading
TEMP_DIRECTORY_PATH = '/tmp'
# Target directory path for extensions
DESTINATION_PATH = '/tmp/extensions'

# Official mediawiki extensions
extensions_official = [
  'TemplateData',
  'TemplateWizard',
  'Graph',
  'TwoColConflict',
  'RevisionSlider',
  'Echo',
  'Thanks',
  'Flow',
  'Scribunto',
  'TemplateStyles',
  'Disambiguator',
  'DisableAccount',
  'AbuseFilter',
  'CheckUser',
  'UserMerge',
  'CodeMirror',
  'CharInsert',
  'Description2',
  'OpenGraphMeta',
  'PageImages',
  'Josa',
  'HTMLTags',
  'BetaFeatures',
  'VisualEditor',
  'Widgets',
  'UniversalLanguageSelector',
  'Translate',
  'LocalisationUpdate',
  'AntiSpoof',
  'UploadWizard',
  'EventLogging',
  'BounceHandler',
  'ContactPage',
]
# 3rd party extensions and their URLs
extensions_3rdparty = {
  'AWS' => 'https://github.com/edwardspec/mediawiki-aws-s3/archive/v0.10.0.tar.gz',
  'EmbedVideo' => 'https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.tar.gz',
  'SimpleMathJax' => 'https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.tar.gz',
  'DiscordNotifications' => 'https://github.com/femiwiki/DiscordNotifications/archive/ko.tar.gz',
}
# Extensions developed by Femiwiki team
extensions_femiwiki = [
  'Sanctions',
  'CategoryIntersectionSearch',
  'FacetedCategory',
  'UnifiedExtensionForFemiwiki',
]

# Names of the all extensions
extensions_all = (
  extensions_official +
  extensions_3rdparty.keys +
  extensions_femiwiki
)
extensions_github = (
  extensions_3rdparty.keys +
  extensions_femiwiki
)

puts 'Started installing extensions'

# Make directories for each extensions
extensions_all.each do |extension|
  FileUtils.mkdir_p "#{DESTINATION_PATH}/extensions/#{extension}"
end

# Create a file that can be used by aria2c with the '--input-file=' option
#
# Reference:
#   https://aria2.github.io/manual/en/html/aria2c.html#id2
input_file = Tempfile.new
input_file.write(
  Parallel.map(extensions_official) do |extension|
    branch_info_url = "https://gerrit.wikimedia.org/r/projects/mediawiki%2Fextensions%2F#{extension}/branches/#{MEDIAWIKI_BRANCH}"
    response = Net::HTTP.get(URI(branch_info_url))
    # Response starts with a magic prefix line ")]}'\n", so strip it.
    # See:
    #   https://gerrit-review.googlesource.com/Documentation/rest-api.html#output
    response = response[5..-1]
    sha = JSON.parse(response)['revision']
    "https://extdist.wmflabs.org/dist/extensions/#{extension}-#{MEDIAWIKI_BRANCH}-#{sha[0..6]}.tar.gz\n out=#{extension}.tar.gz\n"
  end .join +
  extensions_femiwiki.map do |extension|
    "https://github.com/femiwiki/#{extension}/archive/master.tar.gz\n out=#{extension}.tar.gz\n"
  end .join +
  extensions_3rdparty.map do |extension, url|
    "#{url}\n out=#{extension}.tar.gz\n"
  end .join +
  "https://github.com/femiwiki/skin/archive/master.tar.gz\n out=Femiwiki.tar.gz\n"
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

# Install composer dependencies via 'composer update'
# Temporarily do this to all extensions because of https://phabricator.wikimedia.org/T215713
Parallel.each(extensions_all) do |extension|
  next unless File.exist? "#{DESTINATION_PATH}/extensions/#{extension}/composer.json"

  # '/var/www/.composer' is not writable for www-data. Overriding $COMPOSER_HOME
  `COMPOSER_HOME=#{COMPOSER_HOME_PATH} composer update --no-dev --working-dir '#{DESTINATION_PATH}/extensions/#{extension}'`
end

# Install Femiwiki Skin which should be located under 'skins' directory
FileUtils.mkdir_p "#{DESTINATION_PATH}/skins/Femiwiki"
`tar -xzf #{TEMP_DIRECTORY_PATH}/Femiwiki.tar.gz --strip-components=1 --directory #{DESTINATION_PATH}/skins/Femiwiki`

puts 'Finished extension intalling'
