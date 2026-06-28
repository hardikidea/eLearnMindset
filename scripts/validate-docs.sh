#!/usr/bin/env bash
set -euo pipefail

files=(README.md docs/*.md docs/adr/*.md terraform/README.md)

ruby - "${files[@]}" <<'RUBY'
require "pathname"

failures = []

ARGV.each do |file|
  path = Pathname(file)
  next unless path.file?

  content = path.read
  content.scan(/!?\[[^\]]*\]\(([^)]+)\)/).flatten.each do |raw_target|
    target = raw_target.strip.split(/\s+/, 2).first.to_s.delete_prefix("<").delete_suffix(">")
    next if target.empty?
    next if target.start_with?("#", "http://", "https://", "mailto:", "tel:", "//")

    target_path = target.split("#", 2).first
    next if target_path.empty?

    resolved = (path.dirname + target_path).cleanpath
    next if resolved.exist?

    failures << "#{file}: missing local link #{target}"
  end
end

if failures.any?
  warn failures.join("\n")
  exit 1
end

puts "Validated #{ARGV.length} Markdown files."
RUBY
