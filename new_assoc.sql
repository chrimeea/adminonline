insert into users (username, password, admin) values ('adminonline', MD5('intretinere'), 1);

insert into associations (name, address) values (?, ?);

insert into stairs (name, address, id_association) values (?, ?, ?);

insert into persons (name, telephone, email, id_stair, id_person_role, id_apartment, id_person_job, notify) values ('Admin Online', NULL, NULL, ?, 1, NULL, 5, 0);

insert into users_persons_map (id_user, id_person) values (?, ?);

insert into users_stairs_map (id_user, id_stair) values (?, ?);


