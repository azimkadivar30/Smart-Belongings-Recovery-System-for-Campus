# Smart Belongings Recovery System for Campus

A web-based campus lost-and-found platform designed to make reporting,
tracking, identification, and recovery of lost belongings easier,
faster, and more secure.

## 📌 Project Overview

The **Smart Belongings Recovery System for Campus** provides a
centralized platform where students can report lost or found belongings,
upload item images, track recovery status, register smart gadgets, and
receive notifications.

The system also provides:

-   QR-based identification for registered smart gadgets
-   OTP-based ownership verification
-   Email verification for new accounts
-   In-app and email notifications
-   Separate student and admin panels
-   Role-based access control
-   Secure password hashing
-   Session-based authentication
-   Centralized management of users, items, devices, reports, and
    complaints

The project is developed as **Major Project -- I (01CE0716)** for the
B.Tech Computer Engineering program at **Marwadi University, Rajkot**.

## 👥 Team Members

  Name              Enrollment No.
  ----------------- ----------------
  Safik Sherasiya   92410103028
  Nisar Badi        92410103029
  Azim Kadivar      92410103030

### Project Guide

**Prof. Khanjan Trivedi**\
Department of Computer Engineering\
Marwadi University, Rajkot

## ⭐ What Makes This Project Different

Unlike a basic lost-and-found application, this project includes a dedicated **Smart Gadget Recovery** concept.

Students can:

- Register their smart gadgets before they are lost.
- Store device information such as brand, model, and serial number.
- Receive a unique QR Tag associated with the registered device.
- Use the QR Tag to help identify and recover the device if it is found.
- Keep the owner's personal information hidden during the initial identification process.
- Use OTP-based verification to confirm rightful ownership before recovery.

This makes the system useful not only for ordinary lost belongings but also for **smartphones, laptops, tablets, smartwatches, and other registered gadgets**.

## 🎯 Objectives

1.  Develop a centralized web platform for reporting, tracking, and
    recovering lost and found items on campus.
2.  Provide separate student and admin panels.
3.  Implement QR Tags for registered smart gadgets.
4.  Implement OTP-based ownership verification.
5.  Provide in-app and email notifications for important updates.
6.  Improve security through email verification, password hashing,
    session authentication, and role-based access control.

## ✨ Main Features

### Student Features

-   User registration and login
-   Email verification
-   Secure session-based authentication
-   Report lost items
-   Report found items
-   Upload item photographs
-   View reported items
-   Track item status
-   Register smart gadgets
-   Generate/use QR Tags for registered devices
-   QR scanning for device identification
-   OTP-based ownership verification
-   In-app notifications
-   Email notifications
-   Profile management
-   Submit complaints/reports

### Admin Features

-   Admin dashboard
-   Manage registered users
-   Manage lost and found item reports
-   Verify and update item status
-   Manage registered smart devices
-   Manage reports and complaints
-   Coordinate recovery operations
-   Trigger notifications

## 🔄 System Workflow

1.  A student registers using their details.
2.  The system sends an email verification link.
3.  After email verification, the student can log in.
4.  The student can report a lost/found item or register a smart gadget.
5.  The report is initially stored with a pending status.
6.  The admin reviews and manages the report.
7.  The admin updates the recovery status when appropriate.
8.  Students receive in-app and email notifications about important
    updates.
9.  For registered smart gadgets, the QR Tag can be scanned to identify
    the device without exposing the owner's personal information.
10. OTP verification is used to confirm ownership before secure
    recovery.

## 🛠️ Technology Stack

  Layer                 Technology
  --------------------- ------------------------------------------
  Frontend              HTML5, CSS3, JavaScript (ES6), Bootstrap
  Backend               PHP
  Database              MySQL
  Database Management   phpMyAdmin
  Local Server          XAMPP (Apache + MySQL)
  Development OS        Windows

## 🗄️ Database

The system uses MySQL as its relational database.

The main database tables are:

-   `users` -- stores student/admin account information
-   `items` -- stores lost/found item reports
-   `devices` -- stores registered smart gadgets and QR information
-   `notifications` -- stores user notifications
-   `reports` -- stores complaints/reports

The tables use relationships and foreign keys to connect users, items,
devices, notifications, and reports.

## 🔐 Security Features

The project includes several security mechanisms:

-   Email verification
-   Bcrypt password hashing
-   Session-based authentication
-   Role-based access control
-   OTP-based ownership verification
-   Privacy-preserving QR device identification
-   Unique QR tokens for registered devices

## 💻 Running the Project Locally

### Requirements

Before running the project, install:

-   XAMPP
-   Apache
-   MySQL
-   A modern web browser

### Setup

1.  Install and open **XAMPP**.
2.  Start **Apache** and **MySQL**.
3.  Place the project folder inside the XAMPP `htdocs` directory.
4.  Open **phpMyAdmin**.
5.  Create/import the project's MySQL database.
6.  Configure the PHP database connection according to the local MySQL
    configuration.
7.  Open the project through the local Apache server in your browser.

Example:

``` text
http://localhost/<project-folder>/
```

> Replace `<project-folder>` with the actual project folder name used in
> your XAMPP `htdocs` directory.

## 📂 Project Modules

The application is organized around the following modules:

-   Login & Registration
-   Student Dashboard
-   Report Lost/Found Item
-   My Items & Status Tracking
-   QR Tag for Smart Gadgets
-   Device Registration & Recovery
-   Notifications
-   Profile Management
-   Admin Panel
-   User Management
-   Item Management
-   Reports / Complaints

## 📊 Item Status Flow

``` text
Pending
   ↓
Found
   ↓
Recovered
   ↓
Closed
```

The system allows students to monitor the progress of their reported
items through the recovery process.

## 📱 Smart Gadget Recovery

Registered smart gadgets can be associated with a unique QR Tag.

When the QR Tag is scanned:

1.  The registered device can be identified.
2.  The owner's personal details are not directly exposed.
3.  The recovery process can be initiated.
4.  OTP verification can be used to confirm ownership before handover.

## 🔐 Smart Gadget Recovery — Core Feature

The smart-device module is one of the main concepts of this project.

### How it works

```text
Register Smart Gadget
        ↓
Generate / Assign QR Tag
        ↓
Attach QR Tag to the Gadget
        ↓
Gadget Gets Lost
        ↓
Finder Scans QR Tag
        ↓
Recovery Process Starts
        ↓
Owner Verification Using OTP
        ↓
Secure Recovery / Handover
```

The QR Tag is designed to provide a simple way to connect a found gadget with its registered recovery process **without directly exposing the owner's personal information**.

The system is therefore intended as a combination of:

**Campus Lost & Found + Smart Gadget Registration + QR Identification + Secure Recovery**

## 📧 Notification System

The system provides notifications for important events such as:

-   Email verification
-   Item submission
-   Item status updates
-   Device/QR-related updates
-   Recovery-related updates
-   OTP verification
-   Matching/found item notifications

Notifications can be delivered through the application's notification
system and email.

## 📸 Project Results

The implemented system includes interfaces for:

-   Landing Page
-   Registration Page
-   Login Page
-   Email Verification Page
-   Student Dashboard
-   Report Lost/Found Item Page
-   My Items Page
-   My Device Page
-   QR Scan Page
-   Notifications Page
-   My Profile Page
-   Admin Dashboard
-   Manage Items Page
-   Update Item Status Pages
-   Manage Users Page
-   Reports / Complaints Page

## 🚀 Future Scope

The project can be further enhanced with:

-   Dedicated Android and iOS mobile applications
-   SMS notifications
-   Advanced administrator analytics
-   Recovery-rate reports and trends
-   Integration with campus ID/LMS systems
-   AI-based image matching for lost and found items
-   Cloud deployment
-   Multi-campus support

## 🎓 Academic Project & License

This project was developed as an **academic Major Project – I** for the B.Tech Computer Engineering program at Marwadi University.

The source code and implementation are intended for **academic, educational, and demonstration purposes**. The project was developed by the listed student team as part of their college project work.

Please do not present, submit, or redistribute this project as your own academic work. If any part of the repository is reused, modified, or referenced, appropriate credit should be given to the original project team.

### License

Unless a separate license file is added to this repository, **all rights are reserved by the project authors**. No permission is granted to use this project for commercial purposes or to submit it as another person's academic project without prior permission.

## 📚 References

-   W3Schools -- HTML, CSS and JavaScript Tutorials\
    https://www.w3schools.com
-   MDN Web Docs\
    https://developer.mozilla.org
-   PHP Documentation\
    https://www.php.net/docs.php
-   MySQL Documentation\
    https://dev.mysql.com/doc/
-   GitHub\
    https://github.com
-   XAMPP\
    https://www.apachefriends.org
-   phpMyAdmin\
    https://www.phpmyadmin.net

## 🎓 Academic Information

**Project:** Smart Belongings Recovery System for Campus\
**Course:** Major Project -- I (01CE0716)\
**Program:** Bachelor of Technology in Computer Engineering\
**Semester:** 7th Semester\
**Academic Year:** 2026--27\
**University:** Marwadi University, Rajkot\
**Year:** August 2026

------------------------------------------------------------------------

## 📄 Project Report

The complete project report contains the system background, problem
statement, objectives, literature review, proposed system, architecture,
implementation details, database design, results, conclusion, and future
scope.
