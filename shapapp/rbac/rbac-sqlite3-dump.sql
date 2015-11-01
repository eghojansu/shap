-- @author eghojansu <ekokurniawan@panturaweb.com>

-- -----------------------------------------------------
-- Schema rbac
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
  role_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  role CHAR(64) UNIQUE NOT NULL COLLATE NOCASE
);

INSERT INTO roles VALUES(1,'developer');
INSERT INTO roles VALUES(2,'administrator');

CREATE TABLE IF NOT EXISTS permissions (
  permission_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  permission CHAR(64) UNIQUE NOT NULL COLLATE NOCASE
);

INSERT INTO permissions VALUES(1,'Create role');
INSERT INTO permissions VALUES(2,'Read roles');
INSERT INTO permissions VALUES(3,'Update role');
INSERT INTO permissions VALUES(4,'Delete role');
INSERT INTO permissions VALUES(5,'Create permission');
INSERT INTO permissions VALUES(6,'Read permissions');
INSERT INTO permissions VALUES(7,'Update permission');
INSERT INTO permissions VALUES(8,'Delete permission');
INSERT INTO permissions VALUES(9,'Create user role');
INSERT INTO permissions VALUES(10,'Read user roles');
INSERT INTO permissions VALUES(11,'Update user role');
INSERT INTO permissions VALUES(12,'Delete user role');

CREATE TABLE IF NOT EXISTS user_roles (
  user_id CHAR(32) NOT NULL COLLATE NOCASE,
  role_id INTEGER NOT NULL,
  PRIMARY KEY (user_id, role_id)
);

INSERT INTO user_roles VALUES('eghojansu',1);
INSERT INTO user_roles VALUES('eghojansu',2);

CREATE TABLE IF NOT EXISTS roles_permissions (
  role_id INTEGER NOT NULL,
  permission_id INTEGER NOT NULL,
  PRIMARY KEY (role_id, permission_id)
);

INSERT INTO roles_permissions VALUES(1,1);
INSERT INTO roles_permissions VALUES(1,2);
INSERT INTO roles_permissions VALUES(1,3);
INSERT INTO roles_permissions VALUES(1,4);
INSERT INTO roles_permissions VALUES(1,5);
INSERT INTO roles_permissions VALUES(1,6);
INSERT INTO roles_permissions VALUES(1,7);
INSERT INTO roles_permissions VALUES(1,8);
INSERT INTO roles_permissions VALUES(1,9);
INSERT INTO roles_permissions VALUES(1,10);
INSERT INTO roles_permissions VALUES(1,11);
INSERT INTO roles_permissions VALUES(1,12);
INSERT INTO roles_permissions VALUES(2,9);
INSERT INTO roles_permissions VALUES(2,10);
INSERT INTO roles_permissions VALUES(2,11);
INSERT INTO roles_permissions VALUES(2,12);

CREATE VIEW IF NOT EXISTS view_user_roles AS
SELECT
  t1.user_id,
  t1.role_id,
  t2.role
FROM user_roles t1
INNER JOIN roles t2 ON t2.role_id = t1.role_id;

CREATE VIEW IF NOT EXISTS view_user_permissions AS
SELECT
  distinct t3.permission_id as permission_id,
  t3.permission,
  t1.user_id
FROM user_roles t1
INNER JOIN roles t2 ON t2.role_id = t1.role_id
INNER JOIN (
roles_permissions t4 
    INNER JOIN permissions t3 ON t3.permission_id = t4.permission_id
) ON t4.role_id = t2.role_id;

CREATE VIEW IF NOT EXISTS view_roles_permissions AS
SELECT
  t1.role_id,
  t1.role,
  t2.permission_id,
  t2.permission
FROM roles_permissions t3
INNER JOIN permissions t2 ON t2.permission_id = t3.permission_id
INNER JOIN roles t1 ON t1.role_id = t3.role_id;

CREATE TRIGGER delete_role_permissions AFTER DELETE ON roles
BEGIN
  DELETE FROM roles_permissions WHERE role_id = OLD.role_id;
END;

CREATE TRIGGER delete_permission_roles AFTER DELETE ON permissions
BEGIN
  DELETE FROM roles_permissions WHERE permission_id = OLD.permission_id;
END;
