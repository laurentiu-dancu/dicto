-- auto-generated definition
create table a_author
(
    id   INT(10) not null
        primary key auto_increment,
    name VARCHAR(255)
);

-- auto-generated definition
create table a_related_definition
(
    id                INT(10) not null
        primary key auto_increment,
    term              VARCHAR(255)  not null,
    def               VARCHAR(2048) not null,
    undefined_term_id INT(10)
        references a_undefined_term
);

-- auto-generated definition
create table a_tag
(
    id   INT(10) not null
        primary key auto_increment,
    name VARCHAR(255),
    slug VARCHAR(255)
);

-- auto-generated definition
create table a_tag_definition
(
    id      INT(10) not null
        primary key auto_increment,
    tag_id  INT(10) not null
        references a_tag,
    definition_id INT(10) not null
        references a_definition
);

-- auto-generated definition
create table a_definition
(
    id         INT(10) not null
        primary key auto_increment,
    term       VARCHAR(512),
    slug       VARCHAR(512),
    example    VARCHAR(2048),
    def        VARCHAR(2048),
    author_id  INT(10)
        references a_author,
    score_up   INT(10),
    score_down INT(10),
    createdAt  DATE
);

-- auto-generated definition
create table a_undefined_term
(
    id   INT(10) not null
        primary key auto_increment,
    term VARCHAR(255) not null
);

alter table a_definition add foreign key (author_id) references a_author(id);
alter table a_tag_definition add foreign key (definition_id) references a_definition(id);
alter table a_tag_definition add foreign key (tag_id) references a_tag(id);
alter table a_related_definition add foreign key (undefined_term_id) references a_undefined_term(id);

-- auto-generated definition
create table unique_definition
(
    id         INT(10) not null
        primary key auto_increment,
    original_id         INT(10) not null,
    term       VARCHAR(512),
    slug       VARCHAR(512),
    example    VARCHAR(2048),
    def        VARCHAR(2048),
    author_id  INT(10)
        references a_author,
    score_up   INT(10),
    score_down INT(10),
    createdAt  DATE
);

create table unique_related_term(
                                    id int primary key auto_increment not null,
                                    term varchar(255),
                                    def varbinary(2048),
                                    undefined_term_id int not null
);
