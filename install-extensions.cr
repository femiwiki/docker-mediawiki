#
# Configs
#
mediawiki_branch = "REL1_31" # TODO: argv[1] 써야함

# Temporary directory path for downloading
tmp = "/tmp"
# Temporary directory path for Composer
composer_home = "/tmp/composer"

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
]
# Official mediawiki extensions with git submodules
extensions_official_submodule = [
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
  extensions_official_submodule +
  extensions_3rdparty.keys +
  extensions_femiwiki
)
extensions_github = (
  extensions_3rdparty.keys +
  extensions_femiwiki
)

# Make directories for each extensions
extensions_all.each do |extension|
  # TODO: mkdir -p <dirname> 0700
  puts "mkdir -p /srv/femiwiki.com/extensions/#{extension}"
end

# Build string that can be used by aria2c with the '--input-file=-' option
#
# Reference:
#   https://aria2.github.io/manual/en/html/aria2c.html#id2
input =
  extensions_official.map do |extension|
    "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/#{extension}/+archive/#{mediawiki_branch}.tar.gz\n out=#{extension}.tar.gz\n"
  end .join +
  extensions_femiwiki.map do |extension|
    "https://github.com/femiwiki/#{extension}/archive/master.tar.gz\n out=#{extension}.tar.gz\n"
  end .join +
  extensions_3rdparty.map do |extension, url|
    "#{url}\n out=#{extension}.tar.gz\n"
  end .join +
  "https://github.com/femiwiki/skin/archive/master.tar.gz\n out=Femiwiki.tar.gz\n";

# Execute aria2
# TODO
puts "aria2c --input-file=- --dir=#{tmp}"

# Uncompress tar.gz files not from github
extensions_official.each do |extension|
  # TODO
  puts "tar -xzf '#{tmp}/#{extension}.tar.gz' --directory '/srv/femiwiki.com/extensions/#{extension}'"
  # TODO
  puts "rm #{tmp}/#{extension}.tar.gz"
end

# Uncompress tar.gz files from github. It have to be striped component
extensions_github.each do |extension|
  # TODO
  puts "tar -xzf '#{tmp}/#{extension}.tar.gz' --strip-components=1 --directory '/srv/femiwiki.com/extensions/#{extension}'"
  # TODO
  puts "rm #{tmp}/#{extension}.tar.gz"
end

# Clone other extensions
extensions_official_submodule.each do |extension|
  puts "git clone -b '#{mediawiki_branch}' --depth 1 --recurse-submodules 'https://gerrit.wikimedia.org/r/p/mediawiki/extensions/#{extension}' '/srv/femiwiki.com/extensions/#{extension}'"
end

# Install composer dependencies via 'composer update'
extensions_all.each do |extension|
  # TODO: 아래 파일이 존재할 경우에만 진행해야함
  "/srv/femiwiki.com/extensions/#{extension}/composer.json"

  # '/var/www/.composer' is not writable for www-data. Overriding $COMPOSER_HOME
  # TODO
  puts "COMPOSER_HOME=#{composer_home} composer update --no-dev --working-dir '/srv/femiwiki.com/extensions/#{extension}'"
end

# Install Femiwiki Skin which should be located under 'skins' directory
# TODO
puts "mkdir -p /srv/femiwiki.com/skins/Femiwiki"
puts "chmod 700 /srv/femiwiki.com/skins/Femiwiki"
puts "tar -xzf #{tmp}/Femiwiki.tar.gz --strip-components=1 --directory /srv/femiwiki.com/skins/Femiwiki"
puts "rm /tmp/Femiwiki.tar.gz"

# TODO: 파일 하나하나 rm하지말고 rm -rf 로 한번에 치우면 빠를듯
