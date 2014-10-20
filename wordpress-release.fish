#!/usr/local/bin/fish
for line in (git diff --name-only v0.1.9| grep -v ditz); cp  $line ~/Projects/Orbital-wp/trunk/$line ; end;
