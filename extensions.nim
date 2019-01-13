import tables
import sequtils
import strutils
import strformat
import sugar

#
# Configs
#
let
  # TODO: argv[1] 써야함
  mediawiki_branch = "REL1_31"
  tmp = "/tmp"
  composer_home = "/tmp/composer"
  extensions_official = @[
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
  extensions_official_submodule = @[
    "VisualEditor",
    "Widgets",
  ]
  extensions_3rdparty = {
    "AWS": "https://github.com/edwardspec/mediawiki-aws-s3/archive/v0.10.0.tar.gz",
    "EmbedVideo": "https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.tar.gz",
    "SimpleMathJax": "https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.tar.gz",
  }.toTable
  extensions_femiwiki = @[
    "Sanctions",
    "CategoryIntersectionSearch",
    "FacetedCategory",
    "UnifiedExtensionForFemiwiki",
  ]
  extensions_all = (
    extensions_official &
    extensions_official_submodule &
    toSeq(extensions_3rdparty.keys) &
    extensions_femiwiki
  )
  extensions_github = (
    toSeq(extensions_3rdparty.keys) &
    extensions_femiwiki
  )

for extension in extensions_all:
  # TODO: mkdir -p <name> 0700
  let name = &"/srv/femiwiki.com/extensions/{extension}"

let input =
  extensions_official.map(extension =>
    &"https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/{extension}/+archive/{mediawiki_branch}.tar.gz\n out={extension}.tar.gz\n"
  ).join() &
  extensions_femiwiki.map(extension =>
    &"https://github.com/femiwiki/{extension}/archive/master.tar.gz\n out={extension}.tar.gz\n"
  ).join() &
  toSeq(extensions_3rdparty.pairs).map(entry =>
    &"{url}\n out={extension}.tar.gz\n"
  ).join() &
  "https://github.com/femiwiki/skin/archive/master.tar.gz\n out=Femiwiki.tar.gz\n";

echo input
