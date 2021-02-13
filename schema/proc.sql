create procedure insertScore(voteType varchar(255), nid int)
begin
    insert into votingapi_vote(type, uuid, entity_type, entity_id, value, value_type, user_id, timestamp, vote_source,
                               field_name)
    values (voteType, uuid(), 'node', nid, 1, 'option', 0, UNIX_TIMESTAMP(), uuid(), 'field_score');
end;

create procedure loadDownScores()
begin
    DECLARE done BOOLEAN DEFAULT FALSE;
    DECLARE nid INT;
    DECLARE score INT;
    declare counter int default 0;
    DECLARE cur CURSOR FOR SELECT entity_id, field_temp_vote_down_value FROM node__field_temp_vote_down;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done := TRUE;

    OPEN cur;

    testLoop:
    LOOP
        FETCH cur INTO nid, score;
        IF done THEN
            LEAVE testLoop;
        END IF;
        set counter = 0;
        while counter < score
            do
                set counter = counter + 1;
                CALL insertScore('score_down', nid);
            end while;

    END LOOP testLoop;

    CLOSE cur;
end;

create procedure loadUpScores()
begin
    DECLARE done BOOLEAN DEFAULT FALSE;
    DECLARE nid INT;
    DECLARE score INT;
    declare counter int default 0;
    DECLARE cur CURSOR FOR SELECT entity_id, field_temp_vote_up_value FROM node__field_temp_vote_up;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done := TRUE;

    OPEN cur;

    testLoop:
    LOOP
        FETCH cur INTO nid, score;
        IF done THEN
            LEAVE testLoop;
        END IF;
        set counter = 0;
        while counter < score
            do
                set counter = counter + 1;
                CALL insertScore('score_up', nid);
            end while;

    END LOOP testLoop;

    CLOSE cur;
end;

call loadDownScores();
call loadUpScores();
