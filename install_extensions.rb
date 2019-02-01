# frozen_string_literal: true
require 'fileutils'
require 'tempfile'
require 'net/http'
require 'json'
require 'parallel'

opts = {
  "mediawiki_branch" => "REL1_31",
  # Temporary directory path for Composer
  "composer_home_path" => "/tmp/composer/",
  # Temporary directory path for downloading
  "temp_directory_path" => "/tmp/",
  # Target directory path for extensions
  "destination_path" => "/srv/femiwiki.com/",
}

# Official mediawiki extensions
extensions_official = [
  "TemplateData",
  "TwoColConflict",
  "RevisionSlider",
  "Echo",
  "Thanks",
  "Flow",
  "Scribunto",
  "TemplateStyles",
  "Disambiguator",
  "CreateUserPage",
  "AbuseFilter",
  "CheckUser",
  "UserMerge",
  "CodeMirror",
  "CharInsert",
  "Description2",
  "OpenGraphMeta",
  "PageImages",
  "Josa",
  "HTMLTags",
  "BetaFeatures",
  "VisualEditor",
  "Widgets",
]
# 3rd party extensions and their URLs
extensions_3rdparty = {
  "AWS" => "https://github.com/edwardspec/mediawiki-aws-s3/archive/v0.10.0.tar.gz",
  "EmbedVideo" => "https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.tar.gz",
  "SimpleMathJax" => "https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.tar.gz",
}
# Extensions developed by Femiwiki team
extensions_femiwiki = [
  "Sanctions",
  "CategoryIntersectionSearch",
  "FacetedCategory",
  "UnifiedExtensionForFemiwiki",
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

# Get configurations from command line options
ARGV.each do |arg|
  next unless arg =~ /^--.+=.+$/

  key = arg[2..-1].match(/^[^=]+/)[0].gsub("-","_")
  value = arg[2..-1].match(/=(.+)$/)[1]

  next unless opts.has_key?(key)

  opts[key] = value
end

# Remove trailing slash from paths if exists
opts.each { |k, v| v.chomp!("/") if k.end_with?("path") }

puts "Started installing extensions"

# Make directories for each extensions
extensions_all.each do |extension|
  FileUtils.mkdir_p "#{opts["destination_path"]}/extensions/#{extension}"
end

# Create a file that can be used by aria2c with the '--input-file=' option
#
# Reference:
#   https://aria2.github.io/manual/en/html/aria2c.html#id2
input_file = Tempfile.new
input_file.write(
  Parallel.map(extensions_official) do |extension|
    branch_info_url = "https://gerrit.wikimedia.org/r/projects/mediawiki%2Fextensions%2F#{extension}/branches/#{opts["mediawiki_branch"]}"
    response = Net::HTTP.get(URI(branch_info_url))
    # Response starts with a magic prefix line ")]}'\n", so strip it.
    # See:
    #   https://gerrit-review.googlesource.com/Documentation/rest-api.html#output
    response = response[5..-1]
    sha = JSON.parse(response)["revision"]
    "https://extdist.wmflabs.org/dist/extensions/#{extension}-#{opts["mediawiki_branch"]}-#{sha[0..6]}.tar.gz\n out=#{extension}.tar.gz\n"
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
puts "Starting download"
`aria2c --input-file=#{input_file.path} --dir=#{opts["temp_directory_path"]}`
puts "Finished download"

# Uncompress tar.gz files
Parallel.each(extensions_all) do |extension|
  `tar -xzf '#{opts["temp_directory_path"]}/#{extension}.tar.gz' --strip-components=1 --directory '#{opts["destination_path"]}/extensions/#{extension}'`
end

# Install composer dependencies via 'composer update'
Parallel.each(extensions_github) do |extension|
  next unless File.exist? "#{opts["destination_path"]}/extensions/#{extension}/composer.json"

  # '/var/www/.composer' is not writable for www-data. Overriding $COMPOSER_HOME
  `COMPOSER_HOME=#{opts["composer_home_path"]} composer update --no-dev --working-dir '#{opts["destination_path"]}/extensions/#{extension}'`
end

# Install Femiwiki Skin which should be located under 'skins' directory
FileUtils.mkdir_p "#{opts["destination_path"]}/skins/Femiwiki"
`tar -xzf #{opts['temp_directory_path']}/Femiwiki.tar.gz --strip-components=1 --directory #{opts["destination_path"]}/skins/Femiwiki`

puts "Finished extension intalling"
