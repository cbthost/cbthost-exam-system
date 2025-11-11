# 🧠 CBTHost Exam System (PHP Admin Panel)

> **CBTHost.com | The Complete CBT Solution for Schools, Colleges & Training Centers**

[CBTHost.com](https://cbthost.com) is an advanced quiz and exam platform where educators can create quizzes, run offline examinations and sync student results. :contentReference[oaicite:1]{index=1}  
This open-source PHP Admin Panel is designed to work with the CBTHost Offline Software, enabling full local exam administration, student management, result printing, and more.

---

## 🚀 What You Get

- A **PHP + MySQL admin panel** for managing students, exams, cards, results and analyses.  
- Full integration with the CBTHost ecosystem: offline software for examinations + online/cloud sync when connection is available.  
- Support for **Excel import templates** for students and questions — drop files, import, and you're ready to go.

---

## 🧩 Key Features

### 🧑‍🎓 Student & Question Import
- Upload students via Excel/CSV (student ID, full name, email)  
- Upload questions in bulk with Excel template (question text, options, correct answer)  
- Works offline, then syncs with CBTHost platform when connection resumes  

### 🧾 Exam Card & Result Management
- Generate printable exam cards: single student or bulk print  
- Print detailed student results with custom analytics and visual layout  
- Use the combined results analysis feature to merge multiple exam results and export to professional Excel  

### 📊 Combined Results Analysis
- Combine results from 2 or more exams/quizzes  
- Assign custom labels to each exam (e.g., “Midterm”, “Final Project”)  
- Export the combined data to Excel with professional formatting  
- Track student performance across multiple assessments  

### 🔗 Offline Integration
- Works completely offline using CBTHost’s offline exam software, then syncs with online platform  
- Excellent for schools or locations with limited or unreliable internet connectivity  
- Allows seamless transition between fully local mode and hybrid online mode  

---

## 🖥️ System Requirements

- PHP **7.4+**  
- MySQL **5.7+**  
- Apache (via XAMPP, WAMP) or Nginx + PHP on your server  
- Web browser for administration (Chrome or Firefox recommended)

---

## 📁 Excel Templates for Importation

### 🔹 Student Import Template  
| Student ID | Full Name         | Email                |
|------------|-------------------|-----------------------|
| STU0001    | John Doe          | johndoe@example.com   |
| STU0002    | Jane Smith        | janesmith@example.com |

**Notes:**  
- The first row should be headers exactly as shown: *Student ID*, *Full Name*, *Email*.  
- Save file as `.xlsx` or `.csv`.  
- Use the Admin Panel’s “Import Students” function to upload.

### 🔹 Question Import Template  
| Question Text                              | Option A | Option B | Option C | Option D | Correct Answer |
|--------------------------------------------|----------|----------|----------|----------|----------------|
| What is the capital of France?             | London   | Berlin   | Paris    | Madrid   | C              |
| Which element has atomic number 1?         | Helium   | Hydrogen | Oxygen   | Nitrogen | B              |

**Notes:**  
- Header row must exactly match: *Question Text*, *Option A*, *Option B*, *Option C*, *Option D*, *Correct Answer*.  
- Correct Answer should correspond to one of the option letters (A–D).  
- After upload, map the question sets to an exam and category in the system.

---

## ⚙️ Installation & Setup (XAMPP/WAMP)

### Step 1: Clone the Project  
```bash
git clone https://github.com/yourusername/cbthost-exam-system.git
