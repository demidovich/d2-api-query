drop table if exists person;
drop table if exists city;
drop table if exists horse;

create table person (
    id integer,
    first_name text,
    last_name text,
    meta jsonb,
    city_id integer,
    created_at timestamp,
    updated_at timestamp
);

create table city (
    id integer,
    name text
);

create table horse (
    id serial,
    person_id integer,
    name text
);

insert into city (
    id, 
    name
) values 
    (1, 'Winterfell');

insert into person (
    id, 
    first_name, 
    last_name, 
    meta, 
    city_id, 
    created_at
) values 
    (1, 'Jon',      'Snow',      '{"phones":["11111111111"]}', 1, '2021-01-01 00:00:01'),
    (2, 'Jaime',    'Lannister', '{"phones":["22222222222"]}', 1, '2021-01-02 00:00:01'),
    (3, 'Sansa',    'Stark',     '{"phones":["33333333333"]}', 1, '2021-01-03 00:00:01'),
    (4, 'Arya',     'Stark',     '{"phones":["44444444444"]}', 1, '2021-01-04 00:00:01'),
    (5, 'Daenerys', 'Targaryen', '{"phones":["55555555555"]}', 1, '2021-01-05 00:00:01');

insert into horse (
    id,
    person_id,
    name
) values 
    (1, 1, 'Snowball'),
    (2, 2, 'Blackball');
