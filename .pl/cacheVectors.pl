#!/usr/bin/perl

use strict;
use LWP::Simple;

my $out_d  = '../img/vectors';
my $base_u = 'http://localhost/maracoos_assets/vector.php?w=80&h=80&dir=DIR&spd=0&type=arrow&color=COLOR';
my @c = (
   '1558BB'
  ,'b56529'
  ,'1d8538'
);

for (my $i = 0; $i <= 360; $i++) {
  for (my $j = 0; $j <= $#c; $j++) {
    my $u = $base_u;
    $u =~ s/DIR/$i/;
    $u =~ s/COLOR/$c[$j]/;
    my $f = $out_d."/arrow/80x80.dir$i.$c[$j].png";
    print "$u => $f\n";
    getstore($u,$f);
  }
}
