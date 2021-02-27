update a_undefined_term
set term = replace(term, '�n ', 'în ');
update a_undefined_term
set term = replace(term, '�n', 'ân');
update a_undefined_term
set term = replace(term, '�d', 'âd');
update a_undefined_term
set term = replace(term, '�t', 'ât');
update a_undefined_term
set term = replace(term, '�r', 'âr');
update a_undefined_term
set term = replace(term, '�s', 'âs');
update a_undefined_term
set term = replace(term, 'a�', 'aș');
update a_undefined_term
set term = replace(term, '� ', 'ă');
update a_undefined_term
set term = replace(term, '�', 'ă')
where term like '%�';

update a_related_definition, (
    select rd.id, ad.term, ad.def
    from a_related_definition rd
             inner join a_definition ad on rd.def = ad.def
    where rd.term = ''
    group by rd.id, ad.term, ad.def, rd.def
) as j
set a_related_definition.term = j.term
where a_related_definition.id = j.id;

update a_related_definition, (
    select rd.id, rd.term, any_value(ad.def) as def
    from a_related_definition rd
             inner join a_definition ad on rd.term = ad.term
    where rd.def = ''
    group by rd.id, rd.term
) as j
set a_related_definition.def = j.def
where a_related_definition.id = j.id;

insert into unique_related_term (term, def, undefined_term_id)
select distinct rd.term, rd.def, rd.undefined_term_id
from a_related_definition rd
where term != ''
  and def != '';

delete from unique_related_term where term = '';

insert into unique_definition (original_id, term, slug, example, def, author_id, score_up, score_down, createdAt)
select any_value(id), term, any_value(slug), example, def, any_value(author_id), max(score_up) as score_up, max(score_down) as score_down, any_value(createdAt)
from a_definition
group by def, term, example order by score_up;

update unique_related_term, (
    select ud.term, urt.id
    from unique_related_term urt
             inner join unique_definition ud on urt.def = ud.def and ud.def != ''
    where urt.term = ''
    order by urt.term
) as uui
set unique_related_term.term = uui.term where unique_related_term.id = uui.id

update unique_related_term, (
    select distinct any_value(urt.id) as id, urt.term, urt1.def
    from unique_related_term urt
             inner join unique_related_term urt1 on urt.term = urt1.term and urt1.def != ''
    where urt.def = ''
      and urt.term != ''
    group by urt.term, urt1.def
) as uui
set unique_related_term.def = uui.def
where unique_related_term.id = uui.id;

update node__field_exemplu as nfu, (select entity_id, field_exemplu_value
                                    from (select entity_id, field_exemplu_value
                                          from node__field_exemplu
                                          where field_exemplu_value like '%2.%') as x
                                    where x.field_exemplu_value not like "%\r%") as mat
set nfu.field_exemplu_value = replace(nfu.field_exemplu_value, '2.', "\r2.")
where nfu.entity_id  = mat.entity_id;

update node__field_exemplu as nfu, (select entity_id, field_exemplu_value
                                    from (select entity_id, field_exemplu_value
                                          from node__field_exemplu
                                          where field_exemplu_value like '%3.%') as x
                                    where x.field_exemplu_value not like "%\r3.%") as mat
set nfu.field_exemplu_value = replace(nfu.field_exemplu_value, '3.', "\r3.")
where nfu.entity_id  = mat.entity_id;

update node__field_exemplu as nfu, (select entity_id, field_exemplu_value
                                    from (select entity_id, field_exemplu_value
                                          from node__field_exemplu
                                          where field_exemplu_value like '%4.%') as x
                                    where x.field_exemplu_value not like "%\r4.%") as mat
set nfu.field_exemplu_value = replace(nfu.field_exemplu_value, '4.', "\r4.")
where nfu.entity_id  = mat.entity_id;
