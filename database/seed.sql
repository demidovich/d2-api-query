drop table if exists person;
drop table if exists city;
drop table if exists horse;

create table person (
    id integer,
    first_name text,
    last_name text,
    meta jsonb,
    city_id integer,
    created_at timestamp default CURRENT_TIMESTAMP,
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
) values (
    1,
    'Winterfell'
);

insert into person (
    id,
    first_name,
    last_name,
    meta,
    city_id
) values (
    1,
    'John',
    'Snow',
    '{"phones":["71234567890"]}',
    1
);

insert into horse (
    id,
    person_id,
    name
) values (
    1,
    1,
    'Snowball'
);
