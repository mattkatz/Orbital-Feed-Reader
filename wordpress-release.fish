#!/usr/local/bin/fish
for line in (git diff --name-only 0.2.1| grep -v ditz | grep -v fish | grep -v .gitignore | grep -v .md | grep -v publish.sh); cp  $line ~/Projects/Orbital-wp/trunk/$line ; end;
