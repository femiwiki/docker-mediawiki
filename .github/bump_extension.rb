# frozen_string_literal: true

require 'json'

# Get configurations from command line options
if ARGV.empty?
  warn 'No argument passed to'
  exit 1
end
EXTENSION = ARGV[0]
VERSION = ARGV[1]

FILE_PATH = "#{__dir__}/../extension-installer/extensions.json"
data = JSON.parse(File.read(FILE_PATH))

if not data['non-WMF'].key?(EXTENSION)
  warn "There is not an extension named as #{EXTENSION} in extensions.json"
  exit 1
end

data['non-WMF'][EXTENSION]['version'] = VERSION

File.open(FILE_PATH, 'w') { |file| file.write(JSON.pretty_generate(data)+"\n") }
