drop table obs;
drop table station;

create table station (
   seq integer primary key
  ,id varchar unique
  ,name varchar
  ,lon float
  ,lat float
);
create table obs (
   seq integer primary key
  ,station integer
  ,var varchar
  ,uom varchar
  ,t integer
  ,val varchar
  ,foreign key(station) references station(seq)
);
