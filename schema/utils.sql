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

select urt.id, urt.term, ud.term, ud.def, ud.example
from unique_related_term urt
         inner join unique_definition ud on urt.def = ud.def and ud.def != ''
where urt.term = ''
order by urt.term;

update unique_related_term, (
    select ud.term, urt.id
    from unique_related_term urt
             inner join unique_definition ud on urt.def = ud.def and ud.def != ''
    where urt.term = ''
    order by urt.term
) as uui
set unique_related_term.term = uui.term where unique_related_term.id = uui.id

select distinct any_value(urt.id), urt.term, urt1.def from unique_related_term urt
inner join unique_related_term urt1 on urt.term = urt1.term and urt1.def != ''
where urt.def = '' and urt.term != ''
group by urt.term, urt1.def

update unique_related_term, (
    select distinct any_value(urt.id) as id, urt.term, urt1.def
    from unique_related_term urt
             inner join unique_related_term urt1 on urt.term = urt1.term and urt1.def != ''
    where urt.def = ''
      and urt.term != ''
    group by urt.term, urt1.def
) as uui
set unique_related_term.def = uui.def
where unique_related_term.id = uui.id

select urt.id, urt.term, any_value(ad.def) from unique_related_term urt
inner join a_definition ad on ad.term = urt.term
where urt.def = '' and urt.term != ''
group by urt.id, urt.term;
