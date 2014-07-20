#!/usr/local/bin/fish
for line in (git diff --name-only 0.1.7| grep -v ditz); cp  $line ~/Projects/Orbital-wp/trunk/$line ; end;
