# 🏥 Cure Booking System - User Manual

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [User Types & Access Levels](#user-types--access-levels)
4. [Patient User Guide](#patient-user-guide)
5. [Doctor User Guide](#doctor-user-guide)
6. [Clinic User Guide](#clinic-user-guide)
7. [Admin User Guide](#admin-user-guide)
8. [Features Overview](#features-overview)
9. [Troubleshooting](#troubleshooting)
10. [Contact Support](#contact-support)

---

## Introduction

Welcome to the **Cure Booking System** - a comprehensive healthcare management platform that connects patients, doctors, clinics, and laboratories. This system allows you to:

- 📅 **Book Appointments** with doctors
- 🔬 **Order Lab Tests** with home sample collection
- 💊 **Order Medicines** online
- 🏥 **Manage Clinic Operations**
- 👨‍⚕️ **Doctor Practice Management**

---

## Getting Started

### System Requirements
- Web browser (Chrome, Firefox, Safari, Edge)
- Internet connection
- Email address for account creation

### Initial Setup
1. **Access the System**: Visit your Cure Booking System URL
2. **Create Account**: Register as a new user
3. **Email Verification**: Check your email and verify your account
4. **Login**: Use your credentials to access the system

### Default Admin Access
- **Email**: admin@gmail.com
- **Password**: admin123
- ⚠️ **Important**: Change the default password after first login

---

## User Types & Access Levels

### 🧑‍💼 **Admin Users**
- Full system access
- Manage all users, clinics, and doctors
- View all reports and analytics
- System configuration

### 👨‍⚕️ **Doctor Users**
- Manage appointments
- View patient information
- Update availability schedule
- Clinic assignments

### 🏥 **Clinic Users**
- Manage clinic profile
- Handle lab orders
- Upload lab reports
- Manage assigned doctors

### 👤 **Patient Users**
- Book appointments
- Order lab tests
- Order medicines
- View booking history

---

## Patient User Guide

### 📋 **Account Management**

#### Creating Your Account
1. Click **"Register"** on the homepage
2. Fill in your details:
   - Full Name
   - Email Address
   - Mobile Number
   - Password
3. Click **"Create Account"**
4. Check your email for verification link
5. Click the verification link to activate your account

#### Logging In
1. Go to the login page
2. Enter your email and password
3. Click **"Login"**

### 📅 **Booking Appointments**

#### How to Book an Appointment
1. **Login** to your account
2. Click **"Book Appointment"**
3. **Select Doctor** or **Search by Specialty**
4. **Choose Available Date & Time**
5. **Fill Patient Details**:
   - Patient Name
   - Phone Number
   - Email
   - Gender
6. **Review Booking Details**
7. **Confirm Booking**

#### Managing Your Appointments
- **View Appointments**: Go to "My Appointments"
- **Appointment Status**: 
  - 🟡 **Pending**: Waiting for confirmation
  - 🟢 **Confirmed**: Appointment confirmed
  - 🔴 **Cancelled**: Appointment cancelled
  - ✅ **Completed**: Appointment finished
  - ❌ **No Show**: Patient didn't attend

#### Cancelling Appointments
1. Go to **"My Appointments"**
2. Find the appointment you want to cancel
3. Click **"Cancel Appointment"**
4. Confirm cancellation

### 🔬 **Lab Test Booking**

#### Ordering Lab Tests
1. **Login** to your account
2. Click **"Lab Tests"**
3. **Browse Available Tests** or **Search by Name**
4. **Add Tests to Cart**
5. **Review Cart** and total amount
6. **Fill Booking Details**:
   - Customer Name
   - Phone Number
   - Email
   - Complete Address
   - Preferred Collection Date
   - Time Slot
7. **Confirm Order**

#### Available Lab Tests
- **Complete Blood Count (CBC)** - ₹35
- **Basic Metabolic Panel (BMP)** - ₹45
- **Comprehensive Metabolic Panel (CMP)** - ₹65
- **Lipid Panel** - ₹40
- **Thyroid Stimulating Hormone (TSH)** - ₹35
- **Hemoglobin A1C** - ₹45
- **Urinalysis** - ₹25
- **Liver Function Tests** - ₹55
- **C-Reactive Protein (CRP)** - ₹40
- **Vitamin D Test** - ₹60

#### Lab Order Status
- 🟡 **Pending**: Order received
- 🟢 **Confirmed**: Order confirmed
- 🔵 **Sample Collected**: Sample taken
- 🟠 **In Progress**: Test being processed
- ✅ **Completed**: Test completed
- 📄 **Upload Done**: Report uploaded
- 🔴 **Cancelled**: Order cancelled

#### Viewing Lab Reports
1. Go to **"My Lab Orders"**
2. Find completed orders
3. Click **"Download Report"**

### 💊 **Medicine Ordering**

#### How to Order Medicines
1. **Login** to your account
2. Click **"Order Medicines"**
3. **Search for Medicines** by name
4. **Add to Cart** with desired quantity
5. **Review Cart** and total amount
6. **Fill Delivery Details**:
   - Customer Name
   - Phone Number
   - Email
   - Complete Delivery Address
7. **Confirm Order**

#### Available Medicines (Sample)
- **Augmentin 625 Duo Tablet** - ₹223.50
- **Azithral 500 Tablet** - ₹189.75
- **Ascoril LS Syrup** - ₹98.50
- **Allegra 120mg Tablet** - ₹156.25
- **Avil 25 Tablet** - ₹35.60

#### Medicine Order Status
- 🟡 **Pending**: Order received
- 🟢 **Confirmed**: Order confirmed
- 🚚 **Shipped**: Order dispatched
- 📦 **Delivered**: Order delivered
- 🔴 **Cancelled**: Order cancelled

---

## Doctor User Guide

### 👨‍⚕️ **Doctor Account Setup**

#### Creating Doctor Profile
1. **Admin creates** doctor account
2. **Login** with provided credentials
3. **Complete Profile**:
   - Personal Information
   - Specialization
   - Experience
   - Education
   - Bio
   - Consultation Fees
   - Profile Picture

#### Clinic Assignment
- Doctors can be assigned to multiple clinics
- Each clinic assignment includes availability schedule
- Schedule is stored in JSON format for flexibility

### 📅 **Managing Appointments**

#### Viewing Appointments
1. **Login** to doctor account
2. Go to **"My Appointments"**
3. **Filter by**:
   - Date
   - Status
   - Clinic

#### Appointment Actions
- **Confirm Appointment**: Change status to confirmed
- **Mark as Completed**: After consultation
- **Mark as No Show**: If patient doesn't attend
- **View Patient Details**: Access patient information

#### Setting Availability
1. Go to **"Availability Settings"**
2. **Set Working Hours** for each clinic
3. **Mark Unavailable Dates**
4. **Save Changes**

### 📊 **Reports & Analytics**
- **Daily Appointments**: View today's schedule
- **Monthly Reports**: Appointment statistics
- **Patient History**: Previous consultations
- **Earnings Report**: Fee collection summary

---

## Clinic User Guide

### 🏥 **Clinic Profile Management**

#### Setting Up Clinic Profile
1. **Login** to clinic account
2. Go to **"Clinic Profile"**
3. **Update Information**:
   - Clinic Name
   - Contact Details
   - Location
   - Available Timing
   - About Us
   - Profile Image

#### Managing Clinic Status
- **Active**: Accepting bookings
- **Inactive**: Not accepting new bookings
- **Suspended**: Temporarily unavailable

### 👨‍⚕️ **Doctor Management**

#### Adding Doctors to Clinic
1. **Coordinate with Admin** to assign doctors
2. **Set Doctor Availability** for your clinic
3. **Manage Doctor Schedules**

### 🔬 **Lab Order Management**

#### Processing Lab Orders
1. **Login** to clinic account
2. Go to **"Lab Orders"**
3. **View New Orders**
4. **Update Order Status**:
   - Confirm Order
   - Schedule Sample Collection
   - Update Progress
   - Upload Reports

#### Sample Collection
1. **View Collection Schedule**
2. **Print Collection List**
3. **Update Status** after collection
4. **Process Samples**

#### Report Upload
1. **Complete Test Processing**
2. **Upload Report File** (PDF format)
3. **Update Status** to "Upload Done"
4. **Patient Notification** sent automatically

### 💊 **Medicine Inventory** (If Applicable)
- **Manage Medicine Stock**
- **Update Prices**
- **Process Medicine Orders**

---

## Admin User Guide

### 🔧 **System Administration**

#### User Management
1. **View All Users**
2. **Create New Users**:
   - Doctors
   - Clinics
   - Patients
3. **Manage User Status**
4. **Reset Passwords**

#### Clinic Management
1. **Add New Clinics**
2. **Edit Clinic Information**
3. **Manage Clinic Status**
4. **View Clinic Performance**

#### Doctor Management
1. **Add New Doctors**
2. **Assign Doctors to Clinics**
3. **Manage Doctor Profiles**
4. **Set Doctor Availability**

### 📊 **Reports & Analytics**

#### System Reports
- **Total Users**: Active user count
- **Appointments**: Daily/Monthly statistics
- **Lab Orders**: Processing statistics
- **Medicine Orders**: Sales reports
- **Revenue Reports**: Income analysis

#### Data Management
- **Backup Database**
- **Export Reports**
- **Import Data**
- **System Logs**

### ⚙️ **System Configuration**

#### General Settings
- **System Name**
- **Contact Information**
- **Email Configuration**
- **Time Zone Settings**

#### Lab Test Management
1. **Add New Tests**
2. **Update Test Prices**
3. **Manage Test Categories**
4. **Set Sample Types**

#### Medicine Management
1. **Add New Medicines**
2. **Update Medicine Information**
3. **Manage Inventory**
4. **Set Pricing**

---

## Features Overview

### 🔐 **Security Features**
- **Password Encryption**: All passwords are securely hashed
- **Email Verification**: Account activation via email
- **Session Management**: Secure login sessions
- **Role-Based Access**: Different access levels for different users

### 📱 **Mobile Responsive**
- **Responsive Design**: Works on all devices
- **Touch Friendly**: Optimized for mobile use
- **Fast Loading**: Optimized performance

### 🔔 **Notification System**
- **Email Notifications**: For bookings and updates
- **Status Updates**: Real-time status changes
- **Reminder System**: Appointment reminders

### 💾 **Data Management**
- **Automated Backups**: Regular data backups
- **Data Export**: Export reports and data
- **Data Import**: Import bulk data
- **Data Validation**: Ensure data integrity

---

## Troubleshooting

### 🔑 **Login Issues**

#### Cannot Login
1. **Check Email/Password**: Ensure correct credentials
2. **Account Verification**: Check if email is verified
3. **Password Reset**: Use "Forgot Password" link
4. **Clear Browser Cache**: Clear cookies and cache
5. **Contact Support**: If issues persist

#### Email Verification Problems
1. **Check Spam Folder**: Verification email might be in spam
2. **Resend Verification**: Use "Resend Verification" option
3. **Check Email Address**: Ensure correct email is provided
4. **Wait a Few Minutes**: Email delivery might be delayed

### 📅 **Booking Issues**

#### Cannot Book Appointment
1. **Check Doctor Availability**: Ensure doctor is available
2. **Time Slot Conflict**: Choose different time
3. **Login Status**: Ensure you're logged in
4. **Fill All Fields**: Complete all required information

#### Appointment Not Confirmed
1. **Wait for Confirmation**: Appointments need doctor/clinic confirmation
2. **Check Status**: Monitor appointment status
3. **Contact Clinic**: Call clinic directly if urgent
4. **Rebook if Needed**: Book alternative appointment

### 🔬 **Lab Order Issues**

#### Sample Not Collected
1. **Check Collection Date**: Ensure correct date is selected
2. **Verify Address**: Ensure complete address is provided
3. **Contact Clinic**: Call clinic for status update
4. **Reschedule Collection**: Request new collection date

#### Report Not Available
1. **Check Processing Time**: Reports take time to process
2. **Monitor Status**: Check order status regularly
3. **Contact Clinic**: Inquire about report status
4. **Download Issues**: Try different browser if download fails

### 💊 **Medicine Order Issues**

#### Order Not Delivered
1. **Check Delivery Address**: Ensure correct address
2. **Verify Order Status**: Check current status
3. **Contact Support**: Call customer service
4. **Track Order**: Use order tracking if available

### 🌐 **Technical Issues**

#### Website Not Loading
1. **Check Internet Connection**: Ensure stable connection
2. **Try Different Browser**: Test with another browser
3. **Clear Browser Cache**: Delete cookies and cache
4. **Disable Ad Blockers**: Some features might be blocked
5. **Check Server Status**: Contact support if widespread issue

#### Features Not Working
1. **JavaScript Enabled**: Ensure JavaScript is enabled
2. **Browser Compatibility**: Use supported browsers
3. **Update Browser**: Use latest browser version
4. **Disable Extensions**: Temporarily disable browser extensions

---

## Contact Support

### 📞 **Support Channels**

#### Technical Support
- **Email**: contact.curebooking@gmail.com
- **Phone**: +91-XXXX-XXXX-XX
- **Hours**: Monday to Friday, 9 AM to 6 PM

#### Business Hours
- **Monday to Friday**: 9:00 AM - 6:00 PM
- **Saturday**: 9:00 AM - 1:00 PM
- **Sunday**: Closed

#### Emergency Support
- **24/7 Emergency**: +91-XXXX-XXXX-XX
- **Critical Issues Only**: System down, security issues
- **Response Time**: Within 1 hour

### 📧 **Contact Information**

#### General Inquiries
- **Email**: info@curebooking.com
- **Address**: Your Clinic Address Here
- **City**: Your City, State, PIN

#### Feedback & Suggestions
- **Email**: feedback.curebooking@gmail.com
- **Subject**: Include "Feedback" in subject line
- **Response Time**: Within 24 hours

### 📝 **When Contacting Support**

#### Information to Provide
1. **Your Name and Account Email**
2. **Issue Description**: Detailed problem description
3. **Steps to Reproduce**: What you were doing when issue occurred
4. **Browser/Device**: What you're using
5. **Screenshots**: If applicable
6. **Error Messages**: Exact error text

#### Response Times
- **Critical Issues**: Within 1 hour
- **High Priority**: Within 4 hours
- **Medium Priority**: Within 24 hours
- **Low Priority**: Within 48 hours

---

## Appendices

### 📋 **Appendix A: Keyboard Shortcuts**
- **Ctrl + N**: New booking
- **Ctrl + S**: Save changes
- **Ctrl + F**: Search/Filter
- **Esc**: Close modal/dialog
- **Tab**: Navigate between fields

### 📋 **Appendix B: Browser Compatibility**
- **Chrome**: Version 70+
- **Firefox**: Version 65+
- **Safari**: Version 12+
- **Edge**: Version 44+
- **Internet Explorer**: Not supported

### 📋 **Appendix C: File Formats**
- **Profile Images**: JPG, PNG, GIF (Max 5MB)
- **Lab Reports**: PDF (Max 10MB)
- **Data Export**: CSV, PDF, Excel
- **Backup Files**: SQL, ZIP

### 📋 **Appendix D: System Limits**
- **Maximum Users**: Unlimited
- **File Upload Size**: 10MB
- **Concurrent Users**: 1000+
- **Database Size**: Scalable
- **Backup Retention**: 30 days

---

## Version History

### Version 1.0.0 (Current)
- **Initial Release**: July 2025
- **Features**: Complete booking system
- **Modules**: Appointments, Lab Tests, Medicine Orders
- **Users**: Admin, Doctor, Clinic, Patient

### Planned Updates
- **Version 1.1.0**: Mobile app integration
- **Version 1.2.0**: Payment gateway integration
- **Version 1.3.0**: Telemedicine features
- **Version 1.4.0**: Advanced analytics

---

**© 2025 Cure Booking System. All rights reserved.**

*This manual is updated regularly. Please check for the latest version on our website.*