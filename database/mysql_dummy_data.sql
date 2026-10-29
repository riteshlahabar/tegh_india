-- Tegh CRM Dummy Data
-- Login: admin / 1234  |  telecallers: aakanksha,javed,amol / 1234
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM payment_promises;
DELETE FROM calls;
DELETE FROM whatsapp_messages;
DELETE FROM email_messages;
DELETE FROM invoices;
DELETE FROM parties;
DELETE FROM app_settings;
DELETE FROM users;

ALTER TABLE payment_promises AUTO_INCREMENT = 1;
ALTER TABLE calls AUTO_INCREMENT = 1;
ALTER TABLE whatsapp_messages AUTO_INCREMENT = 1;
ALTER TABLE email_messages AUTO_INCREMENT = 1;
ALTER TABLE invoices AUTO_INCREMENT = 1;
ALTER TABLE parties AUTO_INCREMENT = 1;
ALTER TABLE app_settings AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- USERS
INSERT INTO users (id,name,username,email,email_verified_at,password,role,initials,branch,remember_token,created_at,updated_at) VALUES (1,'Tegh Admin','admin','admin@teghcables.com',NOW(),'$2y$12$L2xTOAc9LI.OkynEhlH5AuNovsSrO.rf1tBpZ5IVGp8WBRNzz7xZW','admin','TA','All','2921f330ff5483662f1bba2d811af2bb32ca6b48f5818f06689259230aa4',NOW(),NOW());
INSERT INTO users (id,name,username,email,email_verified_at,password,role,initials,branch,remember_token,created_at,updated_at) VALUES (2,'Aakanksha','aakanksha','aakanksha@teghcables.com',NOW(),'$2y$12$L2xTOAc9LI.OkynEhlH5AuNovsSrO.rf1tBpZ5IVGp8WBRNzz7xZW','telecaller','AK','Mumbai HQ','2c6de43c0f210d5b134f0e322a0d52d1f882419cf2e8aee5113be2043147',NOW(),NOW());
INSERT INTO users (id,name,username,email,email_verified_at,password,role,initials,branch,remember_token,created_at,updated_at) VALUES (3,'Javed','javed','javed@teghcables.com',NOW(),'$2y$12$L2xTOAc9LI.OkynEhlH5AuNovsSrO.rf1tBpZ5IVGp8WBRNzz7xZW','telecaller','JV','Bhiwandi Plant','497ea5ecaa3272c6b03651737fa14ce25e6e4ca53fbf32c97cad928e8f76',NOW(),NOW());
INSERT INTO users (id,name,username,email,email_verified_at,password,role,initials,branch,remember_token,created_at,updated_at) VALUES (4,'Amol','amol','amol@teghcables.com',NOW(),'$2y$12$L2xTOAc9LI.OkynEhlH5AuNovsSrO.rf1tBpZ5IVGp8WBRNzz7xZW','telecaller','AM','Pune Branch','6e4665395c5a246fd95a077c2dff072dc5304ce457b7607bcdf693d30b2d',NOW(),NOW());

-- APP SETTINGS
INSERT INTO app_settings (id,`key`,`value`,created_at,updated_at) VALUES (1,'company_name','Tegh Cables India Pvt. Ltd.',NOW(),NOW());
INSERT INTO app_settings (id,`key`,`value`,created_at,updated_at) VALUES (2,'gstin','27AACCT1234F1Z5',NOW(),NOW());
INSERT INTO app_settings (id,`key`,`value`,created_at,updated_at) VALUES (3,'registered_office','MIDC Industrial Area, Bhiwandi, Maharashtra',NOW(),NOW());
INSERT INTO app_settings (id,`key`,`value`,created_at,updated_at) VALUES (4,'followup_reminder','1 day before due date',NOW(),NOW());
INSERT INTO app_settings (id,`key`,`value`,created_at,updated_at) VALUES (5,'busy_status','Connected',NOW(),NOW());
INSERT INTO app_settings (id,`key`,`value`,created_at,updated_at) VALUES (6,'busy_last_sync','20 Aug 2026, 02:15 PM',NOW(),NOW());

-- PARTIES (28)
INSERT INTO parties (id,name,code,contact_person,mobile,email,city,state,sales_person,branch,assigned_to,credit_limit,payment_terms,busy_party_id,created_at,updated_at) VALUES
(1,'ABC Electricals Pvt Ltd','TC-2000','Rahul Sharma','9818814104','rahul.sharma@abcelect.com','Mumbai','Maharashtra','R. Bhosale','Mumbai HQ',2,366000,'15 Days','BUSY-1000',NOW(),NOW()),
(2,'Shree Power Systems','TC-2001','Amit Patel','9854328389','amit.patel@shreepow.com','Pune','Maharashtra','S. Kapadia','Bhiwandi Plant',3,1123000,'30 Days','BUSY-1001',NOW(),NOW()),
(3,'Om Electrical Industries','TC-2002','Suresh Iyer','9824004925','suresh.iyer@omelectr.com','Ahmedabad','Gujarat','M. Ansari','Ahmedabad Branch',4,600000,'45 Days','BUSY-1002',NOW(),NOW()),
(4,'Maharashtra Cable Distributors','TC-2003','Vikas Mehta','9883431555','vikas.mehta@maharash.com','Surat','Gujarat','P. Deshmukh','Pune Branch',2,1170000,'60 Days','BUSY-1003',NOW(),NOW()),
(5,'Patel Electricals','TC-2004','Ramesh Gupta','9890728639','ramesh.gupta@patelele.com','Vadodara','Gujarat','R. Bhosale','Mumbai HQ',3,1023000,'15 Days','BUSY-1004',NOW(),NOW()),
(6,'Prime Industrial Solutions','TC-2005','Sanjay Rao','9855788562','sanjay.rao@primeind.com','Nashik','Maharashtra','S. Kapadia','Bhiwandi Plant',4,727000,'30 Days','BUSY-1005',NOW(),NOW()),
(7,'Kalyan Electrical Traders','TC-2006','Deepak Joshi','9863248279','deepak.joshi@kalyanel.com','Aurangabad','Maharashtra','M. Ansari','Ahmedabad Branch',2,971000,'45 Days','BUSY-1006',NOW(),NOW()),
(8,'Metro Power & Cables','TC-2007','Kiran Desai','9891150419','kiran.desai@metropow.com','Thane','Maharashtra','P. Deshmukh','Pune Branch',3,1046000,'60 Days','BUSY-1007',NOW(),NOW()),
(9,'Sai Electrical Corporation','TC-2008','Manoj Verma','9892432888','manoj.verma@saielect.com','Bhiwandi','Maharashtra','R. Bhosale','Mumbai HQ',4,627000,'15 Days','BUSY-1008',NOW(),NOW()),
(10,'Gujarat Power Solutions','TC-2009','Ashok Nair','9857135088','ashok.nair@gujaratp.com','Rajkot','Gujarat','S. Kapadia','Bhiwandi Plant',2,698000,'30 Days','BUSY-1009',NOW(),NOW()),
(11,'XYZ Industries','TC-2010','Pravin Shah','9889051358','pravin.shah@xyzindus.com','Mumbai','Maharashtra','M. Ansari','Ahmedabad Branch',3,1068000,'45 Days','BUSY-1010',NOW(),NOW()),
(12,'Vishwakarma Switchgears','TC-2011','Nilesh Kulkarni','9856391699','nilesh.kulkarni@vishwaka.com','Pune','Maharashtra','P. Deshmukh','Pune Branch',4,711000,'60 Days','BUSY-1011',NOW(),NOW()),
(13,'Novatech Power Systems','TC-2012','Ravi Chandran','9816822091','ravi.chandran@novatech.com','Ahmedabad','Gujarat','R. Bhosale','Mumbai HQ',2,923000,'15 Days','BUSY-1012',NOW(),NOW()),
(14,'Deccan Wire & Cable Co.','TC-2013','Yogesh Pawar','9862607603','yogesh.pawar@deccanwi.com','Surat','Gujarat','S. Kapadia','Bhiwandi Plant',3,916000,'30 Days','BUSY-1013',NOW(),NOW()),
(15,'Anand Electricals','TC-2014','Naresh Thakkar','9888124750','naresh.thakkar@anandele.com','Vadodara','Gujarat','M. Ansari','Ahmedabad Branch',4,1070000,'45 Days','BUSY-1014',NOW(),NOW()),
(16,'Shivam Enterprises','TC-2015','Rahul Sharma','9830235734','rahul.sharma@shivamen.com','Nashik','Maharashtra','P. Deshmukh','Pune Branch',2,1193000,'60 Days','BUSY-1015',NOW(),NOW()),
(17,'Trident Electrical Supply','TC-2016','Amit Patel','9881804972','amit.patel@tridente.com','Aurangabad','Maharashtra','R. Bhosale','Mumbai HQ',3,832000,'15 Days','BUSY-1016',NOW(),NOW()),
(18,'Bhagwati Cable House','TC-2017','Suresh Iyer','9885038203','suresh.iyer@bhagwati.com','Thane','Maharashtra','S. Kapadia','Bhiwandi Plant',4,1047000,'30 Days','BUSY-1017',NOW(),NOW()),
(19,'Konark Industrial Traders','TC-2018','Vikas Mehta','9868096561','vikas.mehta@konarkin.com','Bhiwandi','Maharashtra','M. Ansari','Ahmedabad Branch',2,832000,'45 Days','BUSY-1018',NOW(),NOW()),
(20,'Rajlaxmi Electricals','TC-2019','Ramesh Gupta','9849953113','ramesh.gupta@rajlaxmi.com','Rajkot','Gujarat','P. Deshmukh','Pune Branch',3,344000,'60 Days','BUSY-1019',NOW(),NOW()),
(21,'Suryoday Power Corp','TC-2020','Sanjay Rao','9844810246','sanjay.rao@suryoday.com','Mumbai','Maharashtra','R. Bhosale','Mumbai HQ',4,720000,'15 Days','BUSY-1020',NOW(),NOW()),
(22,'Nakshatra Electricals','TC-2021','Deepak Joshi','9893269844','deepak.joshi@nakshatr.com','Pune','Maharashtra','S. Kapadia','Bhiwandi Plant',2,1081000,'30 Days','BUSY-1021',NOW(),NOW()),
(23,'Balaji Wire Traders','TC-2022','Kiran Desai','9871004345','kiran.desai@balajiwi.com','Ahmedabad','Gujarat','M. Ansari','Ahmedabad Branch',3,475000,'45 Days','BUSY-1022',NOW(),NOW()),
(24,'Vardhan Industrial Supplies','TC-2023','Manoj Verma','9828646897','manoj.verma@vardhani.com','Surat','Gujarat','P. Deshmukh','Pune Branch',4,532000,'60 Days','BUSY-1023',NOW(),NOW()),
(25,'Empire Electricals & Cables','TC-2024','Ashok Nair','9861665316','ashok.nair@empireel.com','Vadodara','Gujarat','R. Bhosale','Mumbai HQ',2,640000,'15 Days','BUSY-1024',NOW(),NOW()),
(26,'Marigold Power Solutions','TC-2025','Pravin Shah','9883170785','pravin.shah@marigold.com','Nashik','Maharashtra','S. Kapadia','Bhiwandi Plant',3,303000,'30 Days','BUSY-1025',NOW(),NOW()),
(27,'Aryan Electrotech','TC-2026','Nilesh Kulkarni','9842540340','nilesh.kulkarni@aryanele.com','Aurangabad','Maharashtra','M. Ansari','Ahmedabad Branch',4,921000,'45 Days','BUSY-1026',NOW(),NOW()),
(28,'Hindustan Cable Traders','TC-2027','Ravi Chandran','9854935922','ravi.chandran@hindusta.com','Thane','Maharashtra','P. Deshmukh','Pune Branch',2,536000,'60 Days','BUSY-1027',NOW(),NOW());

-- INVOICES (28)
INSERT INTO invoices (id,party_id,invoice_no,po_no,invoice_date,due_date,invoice_amount,received_amount,outstanding,status,priority,sales_person,branch,assigned_to,last_followup_at,next_followup_on,busy_invoice_id,created_at,updated_at) VALUES
(1,1,'INV-2500','PO-9000','2026-07-24','2026-08-08',606682,0,606682,'Overdue','high','R. Bhosale','Mumbai HQ',2,'2026-08-18 11:00:00','2026-08-22','BINV-2500',NOW(),NOW()),
(2,2,'INV-2503','PO-9002','2026-06-07','2026-07-07',169423,0,169423,'Critical','med','S. Kapadia','Bhiwandi Plant',3,'2026-08-15 11:00:00','2026-08-21','BINV-2503',NOW(),NOW()),
(3,3,'INV-2506','PO-9004','2026-06-20','2026-08-04',91149,0,91149,'Overdue','low','M. Ansari','Ahmedabad Branch',4,'2026-08-18 11:00:00','2026-08-21','BINV-2506',NOW(),NOW()),
(4,4,'INV-2509','PO-9006','2026-06-11','2026-08-10',220436,0,220436,'Overdue','med','P. Deshmukh','Pune Branch',2,'2026-08-16 11:00:00','2026-08-21','BINV-2509',NOW(),NOW()),
(5,5,'INV-2512','PO-9008','2026-06-14','2026-06-29',319412,319412,0,'Collected','low','R. Bhosale','Mumbai HQ',3,NULL,NULL,'BINV-2512',NOW(),NOW()),
(6,6,'INV-2515','PO-9010','2026-07-17','2026-08-16',221456,0,221456,'Overdue','med','S. Kapadia','Bhiwandi Plant',4,'2026-08-18 11:00:00','2026-08-23','BINV-2515',NOW(),NOW()),
(7,7,'INV-2518','PO-9012','2026-06-26','2026-08-10',342546,0,342546,'Overdue','high','M. Ansari','Ahmedabad Branch',2,'2026-08-19 11:00:00','2026-08-20','BINV-2518',NOW(),NOW()),
(8,8,'INV-2521','PO-9014','2026-07-02','2026-08-31',100021,0,100021,'Not Due','med','P. Deshmukh','Pune Branch',3,'2026-08-16 11:00:00','2026-08-22','BINV-2521',NOW(),NOW()),
(9,9,'INV-2524','PO-9016','2026-07-08','2026-07-23',239393,0,239393,'Overdue','med','R. Bhosale','Mumbai HQ',4,'2026-08-18 11:00:00','2026-08-23','BINV-2524',NOW(),NOW()),
(10,10,'INV-2527','PO-9018','2026-07-27','2026-08-26',509305,0,509305,'Not Due','high','S. Kapadia','Bhiwandi Plant',2,'2026-08-19 11:00:00','2026-08-22','BINV-2527',NOW(),NOW()),
(11,11,'INV-2530','PO-9020','2026-06-06','2026-07-21',241268,77206,164062,'Overdue','med','M. Ansari','Ahmedabad Branch',3,'2026-08-17 11:00:00','2026-08-23','BINV-2530',NOW(),NOW()),
(12,12,'INV-2533','PO-9022','2026-07-18','2026-09-16',86325,0,86325,'Not Due','low','P. Deshmukh','Pune Branch',4,'2026-08-18 11:00:00','2026-08-20','BINV-2533',NOW(),NOW()),
(13,13,'INV-2536','PO-9024','2026-07-09','2026-07-24',604294,604294,0,'Collected','low','R. Bhosale','Mumbai HQ',2,NULL,NULL,'BINV-2536',NOW(),NOW()),
(14,14,'INV-2539','PO-9026','2026-07-09','2026-08-08',260722,0,260722,'Overdue','med','S. Kapadia','Bhiwandi Plant',3,'2026-08-18 11:00:00','2026-08-23','BINV-2539',NOW(),NOW()),
(15,15,'INV-2542','PO-9028','2026-06-26','2026-08-10',570754,570754,0,'Collected','low','M. Ansari','Ahmedabad Branch',4,NULL,NULL,'BINV-2542',NOW(),NOW()),
(16,16,'INV-2545','PO-9030','2026-07-10','2026-09-08',308249,0,308249,'Not Due','high','P. Deshmukh','Pune Branch',2,'2026-08-17 11:00:00','2026-08-23','BINV-2545',NOW(),NOW()),
(17,17,'INV-2548','PO-9032','2026-07-11','2026-07-26',60741,21867,38874,'Overdue','low','R. Bhosale','Mumbai HQ',3,'2026-08-16 11:00:00','2026-08-23','BINV-2548',NOW(),NOW()),
(18,18,'INV-2551','PO-9034','2026-07-08','2026-08-07',46216,0,46216,'Overdue','low','S. Kapadia','Bhiwandi Plant',4,'2026-08-18 11:00:00','2026-08-22','BINV-2551',NOW(),NOW()),
(19,19,'INV-2554','PO-9036','2026-06-16','2026-07-31',400495,136168,264327,'Overdue','med','M. Ansari','Ahmedabad Branch',2,'2026-08-17 11:00:00','2026-08-22','BINV-2554',NOW(),NOW()),
(20,20,'INV-2557','PO-9038','2026-07-19','2026-09-17',385572,177363,208209,'Not Due','med','P. Deshmukh','Pune Branch',3,'2026-08-19 11:00:00','2026-08-22','BINV-2557',NOW(),NOW()),
(21,21,'INV-2560','PO-9040','2026-07-28','2026-08-12',209245,98345,110900,'Overdue','med','R. Bhosale','Mumbai HQ',4,'2026-08-18 11:00:00','2026-08-22','BINV-2560',NOW(),NOW()),
(22,22,'INV-2563','PO-9042','2026-07-23','2026-08-22',290617,0,290617,'Not Due','med','S. Kapadia','Bhiwandi Plant',2,'2026-08-18 11:00:00','2026-08-23','BINV-2563',NOW(),NOW()),
(23,23,'INV-2566','PO-9044','2026-07-24','2026-09-07',550086,550086,0,'Collected','low','M. Ansari','Ahmedabad Branch',3,NULL,NULL,'BINV-2566',NOW(),NOW()),
(24,24,'INV-2569','PO-9046','2026-06-24','2026-08-23',205409,0,205409,'Not Due','med','P. Deshmukh','Pune Branch',4,'2026-08-16 11:00:00','2026-08-22','BINV-2569',NOW(),NOW()),
(25,25,'INV-2572','PO-9048','2026-06-17','2026-07-02',462501,0,462501,'Critical','high','R. Bhosale','Mumbai HQ',2,'2026-08-17 11:00:00','2026-08-20','BINV-2572',NOW(),NOW()),
(26,26,'INV-2575','PO-9050','2026-07-06','2026-08-05',144000,0,144000,'Overdue','med','S. Kapadia','Bhiwandi Plant',3,'2026-08-15 11:00:00','2026-08-22','BINV-2575',NOW(),NOW()),
(27,27,'INV-2578','PO-9052','2026-06-07','2026-07-22',394139,98535,295604,'Overdue','med','M. Ansari','Ahmedabad Branch',4,'2026-08-18 11:00:00','2026-08-22','BINV-2578',NOW(),NOW()),
(28,28,'INV-2581','PO-9054','2026-08-03','2026-10-02',356167,210139,146028,'Not Due','med','P. Deshmukh','Pune Branch',2,'2026-08-15 11:00:00','2026-08-21','BINV-2581',NOW(),NOW());

-- PAYMENT PROMISES
INSERT INTO payment_promises (id,party_id,invoice_id,telecaller_id,amount,promise_date,collected_at,status,created_at,updated_at) VALUES
(1,2,2,3,132150,'2026-08-24',NULL,'upcoming',NOW(),NOW()),
(2,7,7,2,315142,'2026-08-19',NULL,'broken',NOW(),NOW()),
(3,10,10,2,315769,'2026-08-20',NULL,'due_today',NOW(),NOW()),
(4,16,16,2,252764,'2026-08-19',NULL,'broken',NOW(),NOW()),
(5,19,19,2,158596,'2026-08-24',NULL,'upcoming',NOW(),NOW()),
(6,21,21,4,82066,'2026-08-20',NULL,'due_today',NOW(),NOW()),
(7,25,25,2,420876,'2026-08-23',NULL,'upcoming',NOW(),NOW());

-- CALLS
INSERT INTO calls (id,party_id,invoice_id,telecaller_id,called_at,outcome,remarks,next_followup_on,promise_amount,promise_date,created_at,updated_at) VALUES
(1,1,1,2,'2026-08-14 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(2,1,1,2,'2026-08-18 11:15:00','Call Later','Customer requested statement of account.','2026-08-22',NULL,NULL,NOW(),NOW()),
(3,1,1,2,'2026-08-14 12:15:00','Call Later','Customer requested statement of account.','2026-08-22',NULL,NULL,NOW(),NOW()),
(4,2,2,3,'2026-08-15 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-21',NULL,NULL,NOW(),NOW()),
(5,2,2,3,'2026-08-19 11:15:00','Call Later','Customer requested statement of account.','2026-08-21',NULL,NULL,NOW(),NOW()),
(6,2,2,3,'2026-08-14 12:15:00','Call Later','Customer requested statement of account.','2026-08-21',NULL,NULL,NOW(),NOW()),
(7,3,3,4,'2026-08-18 10:15:00','Payment Promise','Called accounts department, payment expected shortly.','2026-08-21',NULL,NULL,NOW(),NOW()),
(8,4,4,2,'2026-08-18 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-21',NULL,NULL,NOW(),NOW()),
(9,6,6,4,'2026-08-16 10:15:00','Not Connected','Called accounts department, payment expected shortly.','2026-08-23',NULL,NULL,NOW(),NOW()),
(10,6,6,4,'2026-08-15 11:15:00','Payment Promise','Customer requested statement of account.','2026-08-23',NULL,NULL,NOW(),NOW()),
(11,7,7,2,'2026-08-15 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-20',NULL,NULL,NOW(),NOW()),
(12,7,7,2,'2026-08-19 11:15:00','Call Later','Customer requested statement of account.','2026-08-20',NULL,NULL,NOW(),NOW()),
(13,7,7,2,'2026-08-14 12:15:00','Not Connected','Customer requested statement of account.','2026-08-20',NULL,NULL,NOW(),NOW()),
(14,8,8,3,'2026-08-14 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(15,8,8,3,'2026-08-19 11:15:00','Connected','Customer requested statement of account.','2026-08-22',NULL,NULL,NOW(),NOW()),
(16,8,8,3,'2026-08-19 12:15:00','Payment Promise','Customer requested statement of account.','2026-08-22',NULL,NULL,NOW(),NOW()),
(17,9,9,4,'2026-08-14 10:15:00','Call Later','Called accounts department, payment expected shortly.','2026-08-23',NULL,NULL,NOW(),NOW()),
(18,9,9,4,'2026-08-14 11:15:00','Payment Promise','Customer requested statement of account.','2026-08-23',NULL,NULL,NOW(),NOW()),
(19,9,9,4,'2026-08-17 12:15:00','Connected','Customer requested statement of account.','2026-08-23',NULL,NULL,NOW(),NOW()),
(20,10,10,2,'2026-08-18 10:15:00','Payment Promise','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(21,11,11,3,'2026-08-19 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-23',NULL,NULL,NOW(),NOW()),
(22,12,12,4,'2026-08-14 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-20',NULL,NULL,NOW(),NOW()),
(23,12,12,4,'2026-08-19 11:15:00','Not Connected','Customer requested statement of account.','2026-08-20',NULL,NULL,NOW(),NOW()),
(24,12,12,4,'2026-08-16 12:15:00','Call Later','Customer requested statement of account.','2026-08-20',NULL,NULL,NOW(),NOW()),
(25,14,14,3,'2026-08-17 10:15:00','Not Connected','Called accounts department, payment expected shortly.','2026-08-23',NULL,NULL,NOW(),NOW()),
(26,14,14,3,'2026-08-15 11:15:00','Payment Promise','Customer requested statement of account.','2026-08-23',NULL,NULL,NOW(),NOW()),
(27,16,16,2,'2026-08-17 10:15:00','Payment Promise','Called accounts department, payment expected shortly.','2026-08-23',NULL,NULL,NOW(),NOW()),
(28,16,16,2,'2026-08-18 11:15:00','Connected','Customer requested statement of account.','2026-08-23',NULL,NULL,NOW(),NOW()),
(29,17,17,3,'2026-08-15 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-23',NULL,NULL,NOW(),NOW()),
(30,17,17,3,'2026-08-18 11:15:00','Payment Promise','Customer requested statement of account.','2026-08-23',NULL,NULL,NOW(),NOW()),
(31,17,17,3,'2026-08-17 12:15:00','Payment Promise','Customer requested statement of account.','2026-08-23',NULL,NULL,NOW(),NOW()),
(32,18,18,4,'2026-08-18 10:15:00','Call Later','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(33,19,19,2,'2026-08-19 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(34,20,20,3,'2026-08-16 10:15:00','Not Connected','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(35,20,20,3,'2026-08-16 11:15:00','Payment Promise','Customer requested statement of account.','2026-08-22',NULL,NULL,NOW(),NOW()),
(36,21,21,4,'2026-08-16 10:15:00','Not Connected','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(37,21,21,4,'2026-08-16 11:15:00','Call Later','Customer requested statement of account.','2026-08-22',NULL,NULL,NOW(),NOW()),
(38,22,22,2,'2026-08-17 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-23',NULL,NULL,NOW(),NOW()),
(39,22,22,2,'2026-08-18 11:15:00','Call Later','Customer requested statement of account.','2026-08-23',NULL,NULL,NOW(),NOW()),
(40,24,24,4,'2026-08-19 10:15:00','Call Later','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(41,25,25,2,'2026-08-16 10:15:00','Not Connected','Called accounts department, payment expected shortly.','2026-08-20',NULL,NULL,NOW(),NOW()),
(42,25,25,2,'2026-08-15 11:15:00','Not Connected','Customer requested statement of account.','2026-08-20',NULL,NULL,NOW(),NOW()),
(43,26,26,3,'2026-08-19 10:15:00','Connected','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(44,27,27,4,'2026-08-14 10:15:00','Call Later','Called accounts department, payment expected shortly.','2026-08-22',NULL,NULL,NOW(),NOW()),
(45,27,27,4,'2026-08-15 11:15:00','Payment Promise','Customer requested statement of account.','2026-08-22',NULL,NULL,NOW(),NOW()),
(46,27,27,4,'2026-08-17 12:15:00','Connected','Customer requested statement of account.','2026-08-22',NULL,NULL,NOW(),NOW()),
(47,28,28,2,'2026-08-18 10:15:00','Payment Promise','Called accounts department, payment expected shortly.','2026-08-21',NULL,NULL,NOW(),NOW());

-- Sample WhatsApp + Email logs
INSERT INTO whatsapp_messages (party_id,invoice_id,user_id,mobile,template,message,sent_at,created_at,updated_at) VALUES
(1,1,2,'9876543210','Payment Reminder','Dear Customer, kindly clear outstanding for INV-2500. - Tegh Cables',NOW(),NOW(),NOW()),
(2,2,3,'9812345678','PTP Confirmation','Thank you for the payment promise. We look forward to receiving payment. - Tegh Cables',NOW(),NOW(),NOW());

INSERT INTO email_messages (party_id,invoice_id,user_id,to_email,cc,subject,body,sent_at,created_at,updated_at) VALUES
(1,1,2,'rahul.sharma@abcelect.com',NULL,'Outstanding Reminder - INV-2500','Dear Sir, Please find outstanding details for invoice INV-2500. Regards, Tegh Cables',NOW(),NOW(),NOW()),
(3,3,4,'suresh.iyer@omelectr.com','accounts@teghcables.com','Statement of Account','Dear Sir, Attached is your statement of account. Regards, Tegh Cables',NOW(),NOW(),NOW());

SELECT 'Dummy data inserted successfully' AS message;
