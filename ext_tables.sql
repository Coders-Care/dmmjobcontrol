#
# Table structure for table 'tx_dmmjobcontrol_sector'
#
CREATE TABLE tx_dmmjobcontrol_sector (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    name tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_dmmjobcontrol_category'
#
CREATE TABLE tx_dmmjobcontrol_category (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    name tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_dmmjobcontrol_discipline'
#
CREATE TABLE tx_dmmjobcontrol_discipline (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    name tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_dmmjobcontrol_region'
#
CREATE TABLE tx_dmmjobcontrol_region (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    name tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_dmmjobcontrol_education'
#
CREATE TABLE tx_dmmjobcontrol_education (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    name tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);




#
# Table structure for table 'tx_dmmjobcontrol_job_region_mm'
#
#
CREATE TABLE tx_dmmjobcontrol_job_region_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);




#
# Table structure for table 'tx_dmmjobcontrol_job_sector_mm'
#
#
CREATE TABLE tx_dmmjobcontrol_job_sector_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);




#
# Table structure for table 'tx_dmmjobcontrol_job_category_mm'
#
#
CREATE TABLE tx_dmmjobcontrol_job_category_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);




#
# Table structure for table 'tx_dmmjobcontrol_job_discipline_mm'
#
#
CREATE TABLE tx_dmmjobcontrol_job_discipline_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);




#
# Table structure for table 'tx_dmmjobcontrol_job_education_mm'
#
#
CREATE TABLE tx_dmmjobcontrol_job_education_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);



#
# Table structure for table 'tx_dmmjobcontrol_job'
#
CREATE TABLE tx_dmmjobcontrol_job (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    starttime int(11) DEFAULT '0' NOT NULL,
    endtime int(11) DEFAULT '0' NOT NULL,
    reference tinytext NOT NULL,
    job_title tinytext NOT NULL,
    employer tinytext NOT NULL,
    employer_description text NOT NULL,
    location tinytext NOT NULL,
    region int(11) DEFAULT '0' NOT NULL,
    short_job_description text NOT NULL,
    job_description text NOT NULL,
    experience tinytext NOT NULL,
    job_requirements text NOT NULL,
    job_benefits text NOT NULL,
    apply_information text NOT NULL,
    salary text NOT NULL,
    job_type int(11) DEFAULT '0' NOT NULL,
    contract_type int(11) DEFAULT '0' NOT NULL,
    sector int(11) DEFAULT '0' NOT NULL,
    category int(11) DEFAULT '0' NOT NULL,
    discipline int(11) DEFAULT '0' NOT NULL,
    education int(11) DEFAULT '0' NOT NULL,
	contact int(11) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_dmmjobcontrol_contact'
#
CREATE TABLE tx_dmmjobcontrol_contact (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    name tinytext NOT NULL,
    address text NOT NULL,
    phone tinytext NOT NULL,
    email tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);