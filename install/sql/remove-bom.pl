#!/usr/bin/perl
@file=<>;
$file[0] =~ s/^\xEF\xBB\xBF//;
print(@file);

