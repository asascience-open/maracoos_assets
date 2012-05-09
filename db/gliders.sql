-- drop table track;
-- drop table deployment;
-- drop table provider;
-- drop table type;

create table provider(
   seq integer primary key asc
  ,id char(256) not null
);

create table type(
   seq integer primary key asc
  ,id char(256) not null
);

create table deployment(
   seq integer primary key asc
  ,id char(256) not null
  ,provider integer not null
  ,type integer not null
  ,url char(2056)
  ,t_start timestamp
  ,t_end timestamp
  ,foreign key(provider) references provider(seq)
  ,foreign key(type) references type(seq)
);

create table track(
   seq integer primary key asc
  ,deployment integer not null
  ,t timestamp
  ,lon float
  ,lat float
  ,foreign key(deployment) references deployment(seq)
);

insert into provider(id) values ('scripps');
insert into type(id) values ('spray');
