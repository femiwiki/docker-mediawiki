-- SQL tables for Sanctions extension

CREATE TABLE /*$wgDBprefix*/sanctions (
    st_id INT unsigned NOT NULL AUTO_INCREMENT,
    st_author BIGINT unsigned NOT NULL,
    st_topic binary(11) NOT null,
    st_target BIGINT unsigned NOT NULL,
    st_original_name varchar(255) binary DEFAULT '',
    st_expiry binary(14) NOT NULL,
    st_handled tinyint(1) NOT NULL DEFAULT 0,
    st_emergency tinyint(1) NOT NULL DEFAULT 0,
    st_last_update_timestamp binary(14) NOT NULL,

    PRIMARY KEY (st_id),
    KEY (st_target),
    KEY (st_topic,st_id)
) /*$wgDBTableOptions*/;

CREATE TABLE /*$wgDBprefix*/sanctions_vote (
    stv_id BIGINT unsigned NOT NULL AUTO_INCREMENT,
    stv_topic binary(11) NOT null,
    stv_user BIGINT unsigned NOT NULL,
    stv_period int unsigned,
    stv_last_update_timestamp binary(14) NOT NULL,

    PRIMARY KEY (stv_id),
    KEY (stv_topic,stv_user),
    KEY (stv_id)
) /*$wgDBTableOptions*/;