# Admission Form Setup Guide

## Overview
The admission form system allows the registrar to fill in additional data when approving applications. This data is then used to populate a Word document template that becomes available for download in the student's SIP portal.

## Word Template Setup

### Template Location
Place your Word template file in the `public/` folder with one of these names:
- `delexes_admission_form (1).docx`
- `delexes_admission_form.docx`
- `admission_form.docx`

### Required Placeholders in Word Template

You need to add the following placeholders in your Word document template. These will be replaced with actual data when the form is generated:

#### Student Information Placeholders:
- `{STUDENT_NAME}` - Student's full name
- `{STUDENT_ID}` - Student ID (e.g., 11000001)
- `{EMAIL}` - Student email
- `{PHONE}` - Student phone number
- `{PROGRAM}` - Program name
- `{DEPARTMENT}` - Department name
- `{ACADEMIC_YEAR}` - Academic year
- `{DATE_OF_BIRTH}` - Date of birth (dd/mm/yyyy)
- `{GENDER}` - Gender
- `{NATIONALITY}` - Nationality
- `{ADDRESS}` - Mailing address
- `{ADMISSION_DATE}` - Admission date (dd/mm/yyyy)

#### Admission Form Data Placeholders (filled by registrar):
- `{TOTAL_FEES}` - Total fees amount
- `{MINIMUM_FEE_PERCENTAGE}` - Minimum fee percentage (e.g., "25.00%")
- `{BALANCE_PERCENTAGE}` - Balance percentage (e.g., "75.00%")
- `{PAID_FEES_BY_DATE}` - Paid fees by date (dd/mm/yyyy)
- `{REGISTRATION_BEGINS}` - Registration begins date (dd/mm/yyyy)
- `{ORIENTATION_NEW_STUDENTS}` - Orientation for new students date (dd/mm/yyyy)
- `{FACULTY_ORIENTATION}` - Faculty orientation date (dd/mm/yyyy)
- `{LECTURES_BEGIN}` - Lectures begin date (dd/mm/yyyy)

### How to Add Placeholders in Word

1. Open your Word document template
2. Place your cursor where you want the data to appear
3. Type the placeholder exactly as shown above (e.g., `{STUDENT_NAME}`)
4. Save the document

**Important:** Placeholders must be exact matches (case-sensitive) and include the curly braces `{}`.

## Workflow

1. **Registrar Approves Application:**
   - When registrar clicks "Approve Application", a modal appears
   - Registrar fills in the admission form data fields
   - Clicks "Approve & Generate Form"

2. **System Processing:**
   - Application is approved
   - SIP account is created
   - Admission form data is saved to database
   - Word document is generated with all data populated
   - Download record is created in SIP

3. **Student Access:**
   - Student logs into SIP portal
   - Goes to Downloads section
   - Sees "Admission Form" document
   - Can download the filled form

## Database Structure

The admission form data is stored in the `admission_form_data` table with the following fields:
- `student_id` - Links to student
- `total_fees` - Total fees amount
- `minimum_fee_percentage` - Minimum fee percentage
- `balance_percentage` - Balance percentage
- `paid_fees_by_date` - Paid fees by date
- `registration_begins` - Registration begins date
- `orientation_new_students` - Orientation date
- `faculty_orientation` - Faculty orientation date
- `lectures_begin` - Lectures begin date
- `generated_file_path` - Path to generated Word document

## Troubleshooting

### Template Not Found Error
- Ensure the template file is in the `public/` folder
- Check file name matches one of the expected names
- Verify file permissions allow reading

### Placeholders Not Replaced
- Check placeholder spelling (case-sensitive)
- Ensure curly braces `{}` are included
- Verify placeholder exists in template

### Generated File Not Downloadable
- Check `storage/app/admission_forms/` directory exists
- Verify file permissions
- Check download record was created in database

