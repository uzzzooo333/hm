CREATE DATABASE IF NOT EXISTS mediconnect360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mediconnect360;

-- Users Table
CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'receptionist', 'pharmacist', 'lab_technician', 'billing_staff') NOT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(15),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB;

-- Patients Table
CREATE TABLE patients (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    contact VARCHAR(15) NOT NULL,
    gender ENUM('male', 'female', 'other'),
    dob DATE,
    blood_group VARCHAR(5),
    address TEXT,
    city VARCHAR(100),
    pincode VARCHAR(10),
    emergency_contact VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Appointments Table
CREATE TABLE appointments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    problem TEXT,
    status ENUM('confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
    reminder_sent TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Medicines Table
CREATE TABLE medicines (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    generic_name VARCHAR(255),
    dosage_form ENUM('tablet', 'capsule', 'syrup', 'injection', 'ointment') DEFAULT 'tablet',
    strength VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    expiry_date DATE,
    supplier VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Prescriptions Table
CREATE TABLE prescriptions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED NULL,
    diagnosis TEXT,
    notes TEXT,
    status ENUM('pending', 'dispensed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Prescription Items
CREATE TABLE prescription_items (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT UNSIGNED NOT NULL,
    medicine_name VARCHAR(255) NOT NULL,
    dosage VARCHAR(50),
    frequency VARCHAR(20),
    duration VARCHAR(50),
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Lab Tests Table
CREATE TABLE lab_tests (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    test_name VARCHAR(255) NOT NULL,
    priority ENUM('normal', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'completed') DEFAULT 'pending',
    report_path VARCHAR(500),
    comments TEXT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Bills Table
CREATE TABLE bills (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    payment_method VARCHAR(50) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB;

-- Bill Items
CREATE TABLE bill_items (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    bill_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(50),
    quantity INT NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Beds Table
CREATE TABLE beds (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    bed_number VARCHAR(20) UNIQUE NOT NULL,
    type ENUM('manual', 'semi_electric', 'electric') DEFAULT 'manual',
    ward ENUM('general', 'icu', 'private', 'emergency') NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    patient_id INT UNSIGNED NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Activity Logs
CREATE TABLE activity_logs (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE `telemedicine_appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `meeting_id` varchar(100) NOT NULL,
  `schedule_date` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text,
  `prescription_data` json DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff Attendance Table
CREATE TABLE `staff_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half_day','leave') DEFAULT 'absent',
  `work_hours` decimal(4,2) DEFAULT 0.00,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `remarks` text,
  `face_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `date` (`date`),
  UNIQUE KEY `user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leave Requests Table
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('sick','casual','earned','maternity','unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payroll Table
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `gross_salary` decimal(10,2) NOT NULL,
  `net_salary` decimal(10,2) NOT NULL,
  `working_days` int(11) DEFAULT 0,
  `present_days` int(11) DEFAULT 0,
  `leave_days` int(11) DEFAULT 0,
  `absent_days` int(11) DEFAULT 0,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','paid','hold') DEFAULT 'pending',
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY `user_month_year` (`user_id`, `month`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shift Management Table
CREATE TABLE `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `grace_period_minutes` int(11) DEFAULT 15,
  `working_hours` decimal(4,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Shift Assignment
CREATE TABLE `user_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shift_id` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary Structure Table
CREATE TABLE `salary_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `hra` decimal(10,2) DEFAULT 0.00,
  `medical_allowance` decimal(10,2) DEFAULT 0.00,
  `transport_allowance` decimal(10,2) DEFAULT 0.00,
  `other_allowance` decimal(10,2) DEFAULT 0.00,
  `provident_fund` decimal(10,2) DEFAULT 0.00,
  `professional_tax` decimal(10,2) DEFAULT 0.00,
  `income_tax` decimal(10,2) DEFAULT 0.00,
  `effective_from` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Shifts
INSERT INTO `shifts` (`shift_name`, `start_time`, `end_time`, `grace_period_minutes`, `working_hours`, `is_active`) VALUES
('Morning Shift', '09:00:00', '18:00:00', 15, 9.00, 1),
('Evening Shift', '14:00:00', '22:00:00', 15, 8.00, 1),
('Night Shift', '22:00:00', '06:00:00', 15, 8.00, 1),
('General (9-6)', '09:00:00', '18:00:00', 15, 9.00, 1);


-- Sample Data
INSERT INTO users (name, email, password, role, specialization, phone) VALUES
('Admin User', 'admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, '9876543210'),
('Dr. Amit Sharma', 'doctor@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Cardiologist', '9876543211'),
('Receptionist Neha', 'reception@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', NULL, '9876543212'),
('Pharmacist Raj', 'pharmacy@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacist', NULL, '9876543213'),
('Lab Tech Sunita', 'lab@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lab_technician', NULL, '9876543214'),
('Billing Staff', 'billing@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'billing_staff', NULL, '9876543215');

INSERT INTO patients (name, contact, email, gender, dob, blood_group, address, city, pincode, emergency_contact) VALUES
('Aditya Rao','9720088936','aditya.rao1@example.com','male','1976-03-11','O+','692, Street 17, Sector 14','Mumbai','645325','9695773903'),
('Neha Gupta','9277802977','neha.gupta2@example.com','female','1994-01-27','A+','149, Street 10, Sector 12','Mumbai','124819','9205088063'),
('Rahul Kumar','9479631608','rahul.kumar3@example.com','male','1977-07-09','O+','141, Street 5, Sector 10','Thane','356423','9666658124'),
('Priya Rao','9352597285','priya.rao4@example.com','female','1983-08-13','A-','856, Street 15, Sector 9','Kanpur','570548','9306866063'),
('Rahul Menon','9553868496','rahul.menon5@example.com','male','1974-12-16','A+','353, Street 8, Sector 10','Ghaziabad','729671','9905851975'),
('Pari Joshi','9929669118','pari.joshi6@example.com','female','1989-09-08','O-','524, Street 2, Sector 6','Indore','115762','9337624478'),
('Krishna Rao','9791288421','krishna.rao7@example.com','male','2012-08-29','A+','781, Street 2, Sector 12','Lucknow','537679','9776280567'),
('Navya Singh','9892227123','navya.singh8@example.com','female','2010-10-11','A+','764, Street 1, Sector 7','Delhi','298867','9436311625'),
('Krishna Iyer','9982084077','krishna.iyer9@example.com','male','1964-10-07','B+','578, Street 19, Sector 4','Visakhapatnam','301573','9791977828'),
('Siya Kumar','9984170151','siya.kumar10@example.com','female','1987-10-30','B+','665, Street 17, Sector 4','Patna','783232','9463474589'),
('Vikram Gupta','9820975274','vikram.gupta11@example.com','male','2006-04-14','AB+','292, Street 9, Sector 8','Mumbai','546461','9269338193'),
('Neha Mehta','9571931068','neha.mehta12@example.com','female','1960-07-03','A+','91, Street 15, Sector 6','Jaipur','463194','9179936124'),
('Rahul Verma','9276073403','rahul.verma13@example.com','male','1978-05-04','O-','124, Street 15, Sector 5','Pune','459274','9883435476'),
('Saanvi Kumar','9165416368','saanvi.kumar14@example.com','female','1986-12-14','B-','556, Street 14, Sector 7','Kolkata','118971','9455391311'),
('Ayan Singh','9383388395','ayan.singh15@example.com','male','2018-09-02','O+','39, Street 6, Sector 15','Ahmedabad','680774','9670187417'),
('Anika Menon','9815159230','anika.menon16@example.com','female','1985-11-01','AB-','147, Street 20, Sector 6','Indore','784114','9484228251'),
('Rohan Kapoor','9332052411','rohan.kapoor17@example.com','male','1965-10-21','O-','411, Street 14, Sector 2','Kanpur','472953','9986879973'),
('Aadhya Menon','9371547433','aadhya.menon18@example.com','female','2013-11-04','B-','224, Street 14, Sector 3','Thane','323198','9975027064'),
('Aarav Iyer','9892858603','aarav.iyer19@example.com','male','1986-03-20','B+','707, Street 4, Sector 11','Bhopal','480188','9628553629'),
('Anika Agarwal','9715832100','anika.agarwal20@example.com','female','2008-08-31','A+','919, Street 10, Sector 3','Lucknow','329877','9590052302'),
('Arjun Pillai','9132040303','arjun.pillai21@example.com','male','1998-03-09','B-','461, Street 10, Sector 14','Patna','451295','9552724783'),
('Aaradhya Sharma','9151357797','aaradhya.sharma22@example.com','female','2013-05-25','B+','562, Street 11, Sector 11','Mumbai','798622','9417191005'),
('Reyansh Sharma','9733768652','reyansh.sharma23@example.com','male','1988-09-20','O-','244, Street 13, Sector 7','Kolkata','706005','9284601507'),
('Riya Singh','9245755724','riya.singh24@example.com','female','1988-08-14','B+','618, Street 6, Sector 4','Visakhapatnam','217697','9888532936'),
('Ishaan Kapoor','9578104390','ishaan.kapoor25@example.com','male','1971-05-30','AB-','993, Street 1, Sector 12','Lucknow','334636','9791413878'),
('Neha Kapoor','9215322917','neha.kapoor26@example.com','female','2008-04-04','O-','497, Street 14, Sector 4','Patna','578336','9790210692'),
('Vivaan Joshi','9725210361','vivaan.joshi27@example.com','male','2006-06-09','AB-','392, Street 4, Sector 14','Lucknow','630097','9346038310'),
('Aaradhya Rao','9435095475','aaradhya.rao28@example.com','female','2005-09-23','A-','426, Street 8, Sector 12','Kolkata','785846','9997447480'),
('Vihaan Menon','9842990549','vihaan.menon29@example.com','male','1968-03-03','A+','123, Street 15, Sector 4','Vadodara','774942','9135567293'),
('Neha Kapoor','9450218605','neha.kapoor30@example.com','female','2007-12-09','AB-','991, Street 13, Sector 10','Chennai','382701','9512307389'),
('Arjun Joshi','9877677333','arjun.joshi31@example.com','male','1976-05-10','AB-','594, Street 17, Sector 13','Chennai','562014','9228876199'),
('Sara Mehta','9838564995','sara.mehta32@example.com','female','1981-08-11','AB-','82, Street 8, Sector 13','Delhi','466623','9939865405'),
('Vivaan Patel','9251075939','vivaan.patel33@example.com','male','1986-03-08','AB-','453, Street 6, Sector 4','Ahmedabad','459813','9452867542'),
('Riya Jain','9877434168','riya.jain34@example.com','female','1980-08-03','O+','7, Street 8, Sector 14','Thane','705157','9227793403'),
('Ishaan Patel','9804295148','ishaan.patel35@example.com','male','1968-04-13','AB-','128, Street 14, Sector 13','Kanpur','356772','9936925679'),
('Aadhya Reddy','9579607958','aadhya.reddy36@example.com','female','1999-11-28','O+','501, Street 14, Sector 2','Lucknow','282439','9784069116'),
('Ishaan Gupta','9248157632','ishaan.gupta37@example.com','male','1975-12-26','AB+','67, Street 11, Sector 10','Patna','412199','9423416554'),
('Neha Pillai','9399191207','neha.pillai38@example.com','female','2010-12-25','B+','683, Street 19, Sector 12','Nagpur','260473','9664194317'),
('Amit Iyer','9402389761','amit.iyer39@example.com','male','2016-06-07','O+','614, Street 4, Sector 10','Bhopal','425376','9334827941'),
('Diya Verma','9982815276','diya.verma40@example.com','female','1970-09-20','A+','967, Street 7, Sector 12','Delhi','504216','9210360967'),
('Amit Sharma','9217499451','amit.sharma41@example.com','male','2005-02-16','AB+','902, Street 1, Sector 5','Vadodara','662868','9886573545'),
('Aadhya Patel','9396067356','aadhya.patel42@example.com','female','1973-12-24','O+','596, Street 20, Sector 1','Ahmedabad','366113','9358948468'),
('Vihaan Naidu','9899122374','vihaan.naidu43@example.com','male','1984-07-26','AB+','521, Street 2, Sector 13','Nagpur','461876','9575091001'),
('Aadhya Jain','9791894803','aadhya.jain44@example.com','female','1961-08-14','B+','570, Street 2, Sector 10','Kanpur','120114','9910296614'),
('Sai Menon','9334434499','sai.menon45@example.com','male','1982-04-30','B-','161, Street 6, Sector 1','Bhopal','206796','9893062494'),
('Priya Desai','9216093177','priya.desai46@example.com','female','2007-07-13','A+','681, Street 20, Sector 14','Ahmedabad','176432','9999532120'),
('Amit Nair','9855964236','amit.nair47@example.com','male','1963-02-17','A-','609, Street 7, Sector 8','Nagpur','689540','9565137201'),
('Navya Mehta','9663678186','navya.mehta48@example.com','female','1999-05-28','B+','112, Street 9, Sector 11','Ahmedabad','480329','9884400724'),
('Vikram Desai','9316063787','vikram.desai49@example.com','male','2017-07-11','A-','420, Street 2, Sector 8','Thane','305159','9622118851'),
('Navya Kapoor','9941426498','navya.kapoor50@example.com','female','1983-11-13','AB-','541, Street 20, Sector 12','Bhopal','119819','9317639754');

INSERT INTO medicines (name, generic_name, dosage_form, strength, price, stock_quantity, expiry_date, supplier) VALUES
('Pantoprazole 10ml','Pantoprazole','tablet','10ml',251.38,507,'2026-02-15','Lupin'),
('Clotrimazole 10ml','Clotrimazole','ointment','10ml',225.07,265,'2027-08-23','Zydus'),
('Amoxicillin 1mg','Amoxicillin','capsule','1mg',261.22,567,'2026-10-31','Dr Reddy\'s'),
('Ibuprofen 20mg','Ibuprofen','tablet','20mg',52.88,99,'2026-06-29','Glenmark'),
('Azithromycin 0.5mg','Azithromycin','tablet','0.5mg',163.42,360,'2026-10-21','Abbott'),
('Levocetirizine 400mg','Levocetirizine','tablet','400mg',310.58,102,'2028-11-13','Glenmark'),
('Cefixime 250mg','Cefixime','tablet','250mg',291.53,16,'2029-01-08','Sun Pharma'),
('Atorvastatin 40mg','Atorvastatin','tablet','40mg',200.37,560,'2026-07-27','Mankind'),
('Atorvastatin 0.5mg','Atorvastatin','tablet','0.5mg',272.74,487,'2028-09-01','Dr Reddy\'s'),
('Mupirocin 10ml','Mupirocin','ointment','10ml',318.13,322,'2028-01-18','Dr Reddy\'s'),
('ORS 1mg','Oral rehydration salts','syrup','1mg',176.06,62,'2028-09-05','Abbott'),
('Metformin 10mg','Metformin HCl','tablet','10mg',152.15,234,'2028-04-15','Sun Pharma'),
('Paracetamol 10ml','Acetaminophen','tablet','10ml',44.45,548,'2028-10-28','Cipla'),
('Doxycycline 40mg','Doxycycline','capsule','40mg',9.49,471,'2028-12-30','Lupin'),
('Cefixime 0.5mg','Cefixime','tablet','0.5mg',216.24,586,'2029-02-17','Torrent'),
('Insulin 250mg','Human insulin','injection','250mg',156.51,533,'2029-02-04','Alkem'),
('Cetirizine 650mg','Cetirizine','tablet','650mg',155.47,478,'2026-05-27','Zydus'),
('Ibuprofen 40mg','Ibuprofen','tablet','40mg',95.76,465,'2028-05-11','Abbott'),
('Pantoprazole 650mg','Pantoprazole','tablet','650mg',112.56,467,'2028-09-08','Glenmark'),
('Atorvastatin 250mg','Atorvastatin','tablet','250mg',187.45,224,'2027-08-23','Glenmark'),
('Doxycycline 10mg','Doxycycline','capsule','10mg',336.96,369,'2026-03-19','Zydus'),
('Metformin 1mg','Metformin HCl','tablet','1mg',288.98,481,'2028-06-18','Torrent'),
('Omeprazole 1mg','Omeprazole','capsule','1mg',11.92,373,'2027-11-16','Mankind'),
('Pantoprazole 100mg','Pantoprazole','tablet','100mg',125.41,206,'2026-12-25','Sun Pharma'),
('Mupirocin 1mg','Mupirocin','ointment','1mg',298.65,269,'2027-09-06','Zydus'),
('Insulin 200mg','Human insulin','injection','200mg',334.80,586,'2028-05-10','Torrent'),
('Ibuprofen 500mg','Ibuprofen','tablet','500mg',170.06,552,'2028-03-05','Abbott'),
('Paracetamol 40mg','Acetaminophen','tablet','40mg',46.29,126,'2026-08-19','Glenmark'),
('Salbutamol 10mg','Salbutamol','syrup','10mg',123.34,442,'2028-02-06','Torrent'),
('Azithromycin 10ml','Azithromycin','tablet','10ml',153.96,217,'2028-10-26','Torrent'),
('Clotrimazole 5ml','Clotrimazole','ointment','5ml',279.75,273,'2028-11-07','Alkem'),
('Levocetirizine 40mg','Levocetirizine','tablet','40mg',308.53,134,'2026-08-31','Torrent'),
('Metformin 5mg','Metformin HCl','tablet','5mg',68.69,275,'2027-12-25','Alkem'),
('Ibuprofen 250mg','Ibuprofen','tablet','250mg',120.24,198,'2028-07-08','Torrent'),
('Diclofenac 500mg','Diclofenac','tablet','500mg',345.57,514,'2027-09-29','Sun Pharma'),
('Paracetamol 5mg','Acetaminophen','tablet','5mg',30.70,69,'2028-09-05','Mankind'),
('ORS 20mg','Oral rehydration salts','syrup','20mg',117.41,76,'2027-03-09','Zydus'),
('Ibuprofen 100mg','Ibuprofen','tablet','100mg',343.85,20,'2026-10-31','Zydus'),
('Azithromycin 200mg','Azithromycin','tablet','200mg',78.60,488,'2028-08-13','Cipla'),
('Losartan 40mg','Losartan','tablet','40mg',25.31,42,'2027-12-08','Mankind'),
('Losartan 500mg','Losartan','tablet','500mg',133.27,363,'2027-04-29','Cipla'),
('Clotrimazole 250mg','Clotrimazole','ointment','250mg',126.82,53,'2028-02-02','Torrent'),
('ORS 200mg','Oral rehydration salts','syrup','200mg',49.71,467,'2026-07-31','Mankind'),
('Insulin 20mg','Human insulin','injection','20mg',106.84,83,'2027-03-15','Torrent'),
('Pantoprazole 40mg','Pantoprazole','tablet','40mg',14.42,451,'2029-04-05','Zydus'),
('Amlodipine 10ml','Amlodipine','tablet','10ml',143.12,191,'2027-11-12','Lupin'),
('Doxycycline 250mg','Doxycycline','capsule','250mg',226.56,58,'2028-10-01','Abbott'),
('Cetirizine 10mg','Cetirizine','tablet','10mg',264.27,501,'2027-05-07','Torrent'),
('Metformin 5ml','Metformin HCl','tablet','5ml',338.88,446,'2027-04-26','Glenmark'),
('Salbutamol 650mg','Salbutamol','syrup','650mg',136.28,511,'2026-03-24','Cipla'),
('Amoxicillin 10mg','Amoxicillin','capsule','10mg',41.92,339,'2029-01-19','Cipla'),
('Doxycycline 5mg','Doxycycline','capsule','5mg',313.93,146,'2026-06-02','Dr Reddy\'s'),
('Pantoprazole 1mg','Pantoprazole','tablet','1mg',285.60,21,'2028-07-19','Torrent'),
('Pantoprazole 20mg','Pantoprazole','tablet','20mg',147.30,163,'2026-06-14','Sun Pharma'),
('Ibuprofen 2%','Ibuprofen','tablet','2%',173.68,328,'2029-03-06','Torrent'),
('ORS 0.5mg','Oral rehydration salts','syrup','0.5mg',149.04,116,'2026-05-21','Zydus'),
('Levocetirizine 0.5mg','Levocetirizine','tablet','0.5mg',226.68,460,'2029-02-23','Cipla'),
('Azithromycin 5mg','Azithromycin','tablet','5mg',339.72,591,'2027-04-24','Glenmark'),
('Amoxicillin 500mg','Amoxicillin','capsule','500mg',311.52,454,'2027-10-20','Dr Reddy\'s'),
('Paracetamol 250mg','Acetaminophen','tablet','250mg',320.06,83,'2027-03-27','Sun Pharma'),
('Omeprazole 100mg','Omeprazole','capsule','100mg',340.76,115,'2027-01-07','Torrent'),
('Cefixime 10mg','Cefixime','tablet','10mg',326.49,32,'2028-04-28','Cipla'),
('Pantoprazole 500mg','Pantoprazole','tablet','500mg',153.65,258,'2026-09-08','Glenmark'),
('Diclofenac 650mg','Diclofenac','tablet','650mg',134.29,388,'2026-02-07','Glenmark'),
('Cefixime 200mg','Cefixime','tablet','200mg',119.38,191,'2029-02-19','Abbott'),
('Clotrimazole 0.5mg','Clotrimazole','ointment','0.5mg',246.63,390,'2028-12-20','Mankind'),
('Losartan 100mg','Losartan','tablet','100mg',343.00,126,'2026-12-16','Abbott'),
('Amoxicillin 5ml','Amoxicillin','capsule','5ml',5.30,9,'2028-04-09','Torrent'),
('Ibuprofen 5ml','Ibuprofen','tablet','5ml',206.58,535,'2027-11-12','Lupin'),
('Amoxicillin 650mg','Amoxicillin','capsule','650mg',118.65,370,'2026-04-09','Torrent'),
('Omeprazole 650mg','Omeprazole','capsule','650mg',28.05,556,'2027-01-10','Lupin'),
('Cetirizine 20mg','Cetirizine','tablet','20mg',120.77,395,'2028-10-24','Torrent'),
('Clotrimazole 20mg','Clotrimazole','ointment','20mg',43.17,79,'2029-03-10','Alkem'),
('Paracetamol 200mg','Acetaminophen','tablet','200mg',279.70,192,'2027-08-03','Sun Pharma'),
('Salbutamol 0.5mg','Salbutamol','syrup','0.5mg',170.09,533,'2028-05-02','Torrent'),
('Azithromycin 250mg','Azithromycin','tablet','250mg',37.49,393,'2026-11-08','Cipla'),
('Omeprazole 200mg','Omeprazole','capsule','200mg',238.63,102,'2026-08-05','Torrent'),
('Amoxicillin 0.5mg','Amoxicillin','capsule','0.5mg',35.70,299,'2029-03-04','Torrent'),
('Insulin 100mg','Human insulin','injection','100mg',337.47,468,'2029-03-23','Abbott'),
('Diclofenac 400mg','Diclofenac','tablet','400mg',308.90,453,'2026-10-03','Alkem'),
('ORS 5ml','Oral rehydration salts','syrup','5ml',344.30,273,'2029-03-23','Zydus'),
('ORS 250mg','Oral rehydration salts','syrup','250mg',121.72,79,'2028-02-23','Torrent'),
('Azithromycin 1mg','Azithromycin','tablet','1mg',228.20,424,'2029-01-03','Torrent'),
('Azithromycin 500mg','Azithromycin','tablet','500mg',229.50,114,'2027-10-12','Zydus'),
('Pantoprazole 400mg','Pantoprazole','tablet','400mg',73.46,508,'2027-02-04','Torrent'),
('Insulin 1mg','Human insulin','injection','1mg',187.86,390,'2026-05-25','Mankind'),
('Omeprazole 5ml','Omeprazole','capsule','5ml',291.76,156,'2026-07-03','Zydus'),
('Mupirocin 100mg','Mupirocin','ointment','100mg',199.57,442,'2026-05-17','Torrent'),
('Clotrimazole 1mg','Clotrimazole','ointment','1mg',283.99,562,'2027-07-22','Mankind'),
('Metformin 100mg','Metformin HCl','tablet','100mg',324.45,545,'2028-04-11','Sun Pharma'),
('Azithromycin 650mg','Azithromycin','tablet','650mg',179.23,46,'2027-03-12','Abbott'),
('Metformin 0.5mg','Metformin HCl','tablet','0.5mg',315.83,429,'2028-02-05','Torrent'),
('Azithromycin 40mg','Azithromycin','tablet','40mg',29.83,536,'2026-04-25','Dr Reddy\'s'),
('Pantoprazole 250mg','Pantoprazole','tablet','250mg',178.31,452,'2026-08-31','Mankind'),
('Doxycycline 650mg','Doxycycline','capsule','650mg',6.22,203,'2027-01-20','Zydus'),
('ORS 10ml','Oral rehydration salts','syrup','10ml',186.74,531,'2029-03-18','Torrent'),
('Metformin 20mg','Metformin HCl','tablet','20mg',322.78,235,'2027-02-16','Cipla'),
('Metformin 650mg','Metformin HCl','tablet','650mg',167.86,514,'2026-11-18','Glenmark'),
('Cefixime 100mg','Cefixime','tablet','100mg',324.67,8,'2026-10-07','Torrent');



INSERT INTO beds (bed_number, type, ward, price_per_day, status, patient_id) VALUES
('GEN-101','manual','general',2000.00,'available',NULL),
('GEN-102','manual','general',2000.00,'available',NULL),
('GEN-103','manual','general',2000.00,'available',NULL),
('GEN-104','manual','general',2000.00,'available',NULL),
('GEN-105','manual','general',2000.00,'available',NULL),
('GEN-106','manual','general',2000.00,'available',NULL),
('GEN-107','manual','general',2000.00,'available',NULL),
('GEN-108','manual','general',2000.00,'available',NULL),
('GEN-109','manual','general',2000.00,'available',NULL),
('GEN-110','manual','general',2000.00,'available',NULL),

('GEN-111','manual','general',2000.00,'occupied',1),
('GEN-112','manual','general',2000.00,'occupied',2),
('GEN-113','manual','general',2000.00,'occupied',3),
('GEN-114','manual','general',2000.00,'occupied',4),
('GEN-115','manual','general',2000.00,'occupied',5),
('GEN-116','manual','general',2000.00,'occupied',6),
('GEN-117','manual','general',2000.00,'occupied',7),
('GEN-118','manual','general',2000.00,'occupied',8),
('GEN-119','manual','general',2000.00,'occupied',9),
('GEN-120','manual','general',2000.00,'occupied',10),

('GEN-121','manual','general',2000.00,'maintenance',NULL),
('GEN-122','manual','general',2000.00,'maintenance',NULL),
('GEN-123','manual','general',2000.00,'maintenance',NULL),
('GEN-124','manual','general',2000.00,'maintenance',NULL),
('GEN-125','manual','general',2000.00,'maintenance',NULL),

('PRV-201','semi_electric','private',5000.00,'available',NULL),
('PRV-202','semi_electric','private',5000.00,'available',NULL),
('PRV-203','semi_electric','private',5000.00,'available',NULL),
('PRV-204','semi_electric','private',5000.00,'available',NULL),
('PRV-205','semi_electric','private',5000.00,'available',NULL),
('PRV-206','semi_electric','private',5000.00,'available',NULL),
('PRV-207','semi_electric','private',5000.00,'available',NULL),
('PRV-208','semi_electric','private',5000.00,'available',NULL),
('PRV-209','semi_electric','private',5000.00,'available',NULL),
('PRV-210','semi_electric','private',5000.00,'available',NULL),

('PRV-211','semi_electric','private',5000.00,'occupied',11),
('PRV-212','semi_electric','private',5000.00,'occupied',12),
('PRV-213','semi_electric','private',5000.00,'occupied',13),
('PRV-214','semi_electric','private',5000.00,'occupied',14),
('PRV-215','semi_electric','private',5000.00,'occupied',15),
('PRV-216','semi_electric','private',5000.00,'occupied',16),
('PRV-217','semi_electric','private',5000.00,'occupied',17),
('PRV-218','semi_electric','private',5000.00,'occupied',18),
('PRV-219','semi_electric','private',5000.00,'occupied',19),
('PRV-220','semi_electric','private',5000.00,'occupied',20),

('PRV-221','semi_electric','private',5000.00,'maintenance',NULL),
('PRV-222','semi_electric','private',5000.00,'maintenance',NULL),
('PRV-223','semi_electric','private',5000.00,'maintenance',NULL),
('PRV-224','semi_electric','private',5000.00,'maintenance',NULL),
('PRV-225','semi_electric','private',5000.00,'maintenance',NULL),

('ICU-301','electric','icu',8000.00,'available',NULL),
('ICU-302','electric','icu',8000.00,'available',NULL),
('ICU-303','electric','icu',8000.00,'available',NULL),
('ICU-304','electric','icu',8000.00,'available',NULL),
('ICU-305','electric','icu',8000.00,'available',NULL),
('ICU-306','electric','icu',8000.00,'available',NULL),
('ICU-307','electric','icu',8000.00,'available',NULL),
('ICU-308','electric','icu',8000.00,'available',NULL),
('ICU-309','electric','icu',8000.00,'available',NULL),
('ICU-310','electric','icu',8000.00,'available',NULL),

('ICU-311','electric','icu',8000.00,'occupied',21),
('ICU-312','electric','icu',8000.00,'occupied',22),
('ICU-313','electric','icu',8000.00,'occupied',23),
('ICU-314','electric','icu',8000.00,'occupied',24),
('ICU-315','electric','icu',8000.00,'occupied',25),
('ICU-316','electric','icu',8000.00,'occupied',26),
('ICU-317','electric','icu',8000.00,'occupied',27),
('ICU-318','electric','icu',8000.00,'occupied',28),
('ICU-319','electric','icu',8000.00,'occupied',29),
('ICU-320','electric','icu',8000.00,'occupied',30),

('ICU-321','electric','icu',8000.00,'maintenance',NULL),
('ICU-322','electric','icu',8000.00,'maintenance',NULL),
('ICU-323','electric','icu',8000.00,'maintenance',NULL),
('ICU-324','electric','icu',8000.00,'maintenance',NULL),
('ICU-325','electric','icu',8000.00,'maintenance',NULL),

('EMR-401','manual','emergency',3500.00,'available',NULL),
('EMR-402','manual','emergency',3500.00,'available',NULL),
('EMR-403','manual','emergency',3500.00,'available',NULL),
('EMR-404','manual','emergency',3500.00,'available',NULL),
('EMR-405','manual','emergency',3500.00,'available',NULL),
('EMR-406','manual','emergency',3500.00,'available',NULL),
('EMR-407','manual','emergency',3500.00,'available',NULL),
('EMR-408','manual','emergency',3500.00,'available',NULL),
('EMR-409','manual','emergency',3500.00,'available',NULL),
('EMR-410','manual','emergency',3500.00,'available',NULL),

('EMR-411','manual','emergency',3500.00,'occupied',31),
('EMR-412','manual','emergency',3500.00,'occupied',32),
('EMR-413','manual','emergency',3500.00,'occupied',33),
('EMR-414','manual','emergency',3500.00,'occupied',34),
('EMR-415','manual','emergency',3500.00,'occupied',35),

('EMR-416','manual','emergency',3500.00,'maintenance',NULL),
('EMR-417','manual','emergency',3500.00,'maintenance',NULL),
('EMR-418','manual','emergency',3500.00,'maintenance',NULL),
('EMR-419','manual','emergency',3500.00,'maintenance',NULL),
('EMR-420','manual','emergency',3500.00,'maintenance',NULL);
-- Step 1: Create Categories Table FIRST (parent table)
CREATE TABLE `health_categories` (
  `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `icon` VARCHAR(50),
  `parent_id` INT UNSIGNED NULL,
  `order_position` INT DEFAULT 0,
  FOREIGN KEY (`parent_id`) REFERENCES `health_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Step 2: Insert Sample Categories
INSERT INTO `health_categories` (`name`, `slug`, `description`, `icon`, `parent_id`, `order_position`) VALUES
('Heart Health', 'heart-health', 'Cardiovascular diseases and prevention', 'heart-pulse', NULL, 1),
('Diabetes Care', 'diabetes-care', 'Diabetes management and prevention tips', 'droplet', NULL, 2),
('Mental Health', 'mental-health', 'Mental wellness and stress management', 'brain', NULL, 3),
('Nutrition', 'nutrition', 'Healthy eating and diet plans', 'egg', NULL, 4),
('Exercise & Fitness', 'exercise-fitness', 'Physical activity and workout tips', 'activity', NULL, 5),
('Infectious Diseases', 'infectious-diseases', 'Prevention and treatment of infections', 'virus', NULL, 6),
('Women Health', 'women-health', 'Health issues specific to women', 'gender-female', NULL, 7),
('Child Health', 'child-health', 'Pediatric care and child development', 'person', NULL, 8),
('Preventive Care', 'preventive-care', 'Screening and preventive measures', 'shield-check', NULL, 9),
('Senior Care', 'senior-care', 'Healthcare for elderly patients', 'person-wheelchair', NULL, 10);

-- Step 3: NOW Create Articles Table (child table)
CREATE TABLE `health_articles` (
  `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) UNIQUE NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `author_id` INT UNSIGNED NOT NULL,
  `content` LONGTEXT NOT NULL,
  `excerpt` TEXT,
  `featured_image` VARCHAR(500),
  `tags` VARCHAR(500),
  `views` INT DEFAULT 0,
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `published_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `health_categories`(`id`),
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_category` (`category_id`)
) ENGINE=InnoDB;

-- Step 4: Create Videos Table
CREATE TABLE `health_videos` (
  `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `youtube_id` VARCHAR(50) NOT NULL,
  `thumbnail_url` VARCHAR(500),
  `duration` VARCHAR(20),
  `description` TEXT,
  `views` INT DEFAULT 0,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `health_categories`(`id`),
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- Step 5: Create Health Tips Table
CREATE TABLE `health_tips` (
  `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `tip_type` ENUM('preventive', 'nutrition', 'exercise', 'mental_health', 'general') NOT NULL,
  `priority` INT DEFAULT 0,
  `is_featured` TINYINT(1) DEFAULT 0,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `health_categories`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- Step 6: Create Bookmarks Table
CREATE TABLE `article_bookmarks` (
  `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `article_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`article_id`) REFERENCES `health_articles`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_article` (`user_id`, `article_id`)
) ENGINE=InnoDB;

-- Step 7: Insert Sample Health Tips
INSERT INTO `health_tips` (`title`, `content`, `category_id`, `tip_type`, `priority`, `is_featured`, `created_by`) VALUES
('Stay Hydrated', 'Drink at least 8 glasses of water daily to maintain proper body function and prevent dehydration.', 4, 'general', 5, 1, 2),
('Regular Exercise', '30 minutes of moderate exercise 5 days a week can reduce risk of chronic diseases by 50%.', 5, 'exercise', 5, 1, 2),
('Heart-Healthy Diet', 'Include omega-3 fatty acids, whole grains, and reduce saturated fats to protect your heart.', 1, 'nutrition', 4, 1, 2),
('Mental Wellness', 'Practice mindfulness meditation for 10 minutes daily to reduce stress and anxiety.', 3, 'mental_health', 4, 1, 2),
('Blood Sugar Control', 'Monitor your blood glucose levels regularly and maintain a consistent meal schedule.', 2, 'preventive', 5, 1, 2),
('Hand Hygiene', 'Wash hands with soap for 20 seconds to prevent 80% of common infections.', 6, 'preventive', 5, 1, 2);
