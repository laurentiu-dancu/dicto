# select id, term, def, example, max(score_up) as score_up, max(score_down) as score_down, createdAt, author_id
# from a_definition
# group by def, term, example order by id

insert into unique_definition (original_id, term, example, def, author_id, score_up, score_down, createdAt)
select any_value(id), term,  example, def, any_value(author_id), max(score_up) as score_up, max(score_down) as score_down, any_value(createdAt)
from a_definition
group by def, term, example order by score_up

select ud.id, ud.term, a.name
from unique_definition ud
         inner join a_tag_definition atd on ud.original_id = atd.definition_id
         inner join a_tag a on atd.tag_id = a.id order by ud.term

select t.name, count(t.name) as countName
from a_tag t
         inner join a_tag_definition atd on t.id = atd.tag_id
         inner join unique_definition ud on atd.definition_id = ud.original_id
group by t.name
order by countName desc

