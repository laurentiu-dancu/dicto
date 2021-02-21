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
