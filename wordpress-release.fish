#!/usr/local/bin/fish
for line in (git diff --name-only v0.2| grep -v ditz | grep -v fish | grep -v .gitignore | grep -v .md | grep -v publish.sh); cp  $line ~/Projects/Orbital-wp/trunk/$line ; end;
