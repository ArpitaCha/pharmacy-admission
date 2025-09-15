<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>pharmacy Application Form</title>
<style>
   body {
        font-family: Arial, sans-serif;
        font-size: 13px;
        margin: 8px;
        border: 2px solid #000; /* Outer border */
        padding: 10px;
        /* background-color: antiquewhite; */
       
    }

    table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 10px;
    }

    td, th {
        border: 1px solid #000;
        padding: 5px;
        vertical-align: top;
        font-size:12px;
    }

    .no-border {
        border: none;
    }
    tr {
        font-size:12px;

    }

    .center {
        text-align: center;
    }

    .header {
        font-size: 14px;
        font-weight: bold;
    }

    .photo {
        width: 120px;
        height: 100px;
        border: 1px solid #000;
        background: #eee;
        text-align: center;
        font-size: 12px;
    }

     .qr-code-container {
            width: 60px;
            height: 60px;
            border: 1px solid black;
            float: right;
            margin-top: -3rem;
            margin-right: 10px;
        }

    .logo-container {
        width: 10%;
        text-align: center;
    }

    .header {
        position: fixed;
        top: 1px;
        text-align: center;
    }
</style>

</head>
<body>
@if($type === 'PREVIEW')
    <div style="
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg); /* center + rotate */
        width: 70%;  
        height: 70%;  
        opacity: 0.1; 
        z-index: -1;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(3, 1fr);    
        justify-items: center;
        align-items: center;
        pointer-events: none;
        text-align: center;
    ">
        @for ($i = 0; $i < 9; $i++) 
            <p style="font-size:60px; font-weight:bold; color:red; margin:0;">
                PREVIEW
            </p>
        @endfor
    </div>
@endif




    <div class="header" style="position:fixed;padding-bottom:3px;">
        <div class="logo-container"style="position:absolute;margin-top:5px;margin-left:-5px;">

                 <img 
                        src="{{ public_path('storage/uploads/council_logo.png') }}" 
                        alt="Council Logo" 
                        style="width:50px; height:auto;  display:block; margin-top:20px; margin-left:auto; margin-right:5px;">
        </div>
        <div class="header-text" style="flex-grow: 1;">
            <p style="margin-left: 60px;margin-right:5px;">
                <span style="color:#2d0660;font-family:Cambria;font-size:15px;">
                    <span>
                        <strong>WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL EDUCATION <br>AND SKILL
                            DEVELOPMENT</strong>
                    </span>
                </span>
            </p>
         
           

            
        <p style="margin:0; white-space:nowrap; margin-left:30px; padding-bottom:2px; margin-top:-15px;">
            <span style="font-family:'Trebuchet MS', Helvetica, sans-serif; font-size:12px;">
                Department of Technical Education, Training and Skill Development, Government of West Bengal
            </span>
        </p>

        <p style="margin:5; text-align:center; white-space:nowrap; line-height:11.53px;">
                <span style="font-family:'Trebuchet MS', Helvetica, sans-serif; font-size:12px;">
                    Karigari Bhawan, 4th Floor, Plot No. B/7, Action Area-III, Newtown, Rajarhat, Kolkata–700160
                </span>
        </p>


        </div>
        <hr style="border: 1px solid #000; margin:5px  auto; width:90%;">
      
        <div style="text-align:center; ">
            <p style="font-size:14px; padding-bottom:10px;margin-top:2px;">
            Application Form for Admission to 1st Year of Diploma in Pharmacy (Academic Session 2025-26)
            </p>
        </div>
   

    </div>
    


    <div>
    <table border="1" cellpadding="1" cellspacing="1" style="width:100%;padding-top: 150px;">
            <tbody>
                <tr>
                    <td><strong>Application No. {{$students->s_appl_form_num}}</strong></td>
                    <td style="text-align: center;"><strong>Candidate's Photograph</strong></td>
                    <td style="text-align: center;"><strong>Candidate's Signature </strong></td>
                </tr>
                <tr>
                    <td class="qr-code-container" style="text-align:center;">
                        <img src="data:image/png;base64, {!! $qr_code !!}" alt="">
                        <p style="font-size:10px;">Scan for details</p>
                    </td>
                    <td style="text-align:center;""> @if(!empty($students->s_photo))
                            <img 
                                src="{{ public_path('storage/' . $students->s_photo) }}" 
                                alt="Student Photo" 
                                style="width:100px; height:auto; border:1px solid #000; display:block; margin:0 auto 10px;">
                        @endif</td>
                    <td style="text-align:center;"> @if(!empty($students->s_sign))
                            <img 
                                src="{{ public_path('storage/' . $students->s_sign) }}" 
                                alt="Student Signature" 
                                style="width:140px; height:50px; border:1px solid #000; display:block; margin:0 auto;">
                        @endif</td>
                </tr>
            </tbody>
    </table>
    <table style="padding-top:10px;">
                <tr>
                    <th colspan="4" style="text-align:left; font-size:14px;">Personal Details</th>
                </tr>
                <tr>
                    <td><strong>Candidate's Name</strong></td>
                    <td>{{ $students->s_candidate_name }}</td>
                    <td><strong>Aadhaar No.</strong></td>
                    <td>{{ $students->s_aadhar_no }}</td>

                </tr>

            <tr>
                <td><strong>Father’s Name</strong></td>
                <td>{{ $students->s_father_name }}</td>
                <td><strong>Mother’s Name</strong></td>
                <td>{{ $students->s_mother_name }}</td>
                
            </tr>
            <tr>
                <td><strong>Date of Birth</strong></td>
                <td>{{ \Carbon\Carbon::parse($students->s_dob)->format('d-m-Y') }}</td>
                <td><strong>Age as on 31.12.2025</strong></td>
                <td>
                    {{ $age->y }} Years {{ $age->m }} Months {{ $age->d }} Days
                </td>
            <tr>
                
                <td><strong>Gender</strong></td>
                <td>{{ $students->s_gender }}</td>
                
                <td><strong>Kanyashree No.</strong></td>
                <td>@if($students->s_gender == 'FEMALE'){{ $students->s_kanyashree  }}
                    @else
                        N/A
                    @endif</td>

            </tr>
            
            <tr>
                <td><strong>Religion</strong></td>
                <td>{{ $students->s_religion }}</td>
                <td><strong>Marital Status</strong></td>
                <td>{{ $students->is_married == 1 ? 'MARRIED' : 'UNMARRIED' }}</td>
            </tr>
            <tr>
                <td><strong>Email ID</strong></td>
                <td>{{ $students->s_email }}</td>
                <td><strong>Mobile No.</strong></td>
                <td>{{ $students->s_phone }}</td>
            </tr>
        
    </table>
    <table style="padding-top:10px;">
                <tr>
                    <th colspan="4" style="text-align:left; font-size:14px;">Address Details</th>
                </tr>
                <tr>
                  <td><strong>Address</strong></td>
                <td>{{ $students->s_address }}</td>
                <td><strong>Post Office</strong></td>
                <td>{{ $students->s_post_office }}</td>

                </tr>

            <tr>
                 
                <td><strong>Police Station</strong></td>
                <td>{{ $students->s_police_station }}</td>
                <td><strong>Block</strong></td>
                <td>{{ $student_data['block']['name'] ?? 'N/A' }}</td>
            </tr>
            
            
            <tr>
                 <td><strong>Sub-Division</strong></td>
                <td>{{ $student_data['subdivision']['name'] ?? 'N/A' }}</td>
                <td><strong>District</strong></td>
                <td>{{ $students->district->district_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>State</strong></td>
                <td>{{ $students->state->state_name ?? 'N/A' }}</td>
                <td><strong>PIN No.</strong></td>
                <td>{{ $students->s_pin_no ?? 'N/A' }}</td>

            </tr>
        
    </table>
    @if($students->is_business == '1')
   <table style="padding-top:10px;">
                <tr>
                    <th colspan="4" style="text-align:left; font-size:14px;">Business Address Details</th>
                </tr>
                <tr>
                  <td><strong>Address</strong></td>
                <td>{{ $students->s_address }}</td>
                <td><strong>Post Office</strong></td>
                <td>{{ $students->s_post_office }}</td>

                </tr>

            <tr>
                 
                <td><strong>Police Station</strong></td>
                <td>{{ $students->s_police_station }}</td>
                <td><strong>Block</strong></td>
                <td>{{ $student_data['block']['name'] ?? 'N/A' }}</td>
            </tr>
            
            
            <tr>
                 <td><strong>Sub-Division</strong></td>
                <td>{{ $student_data['subdivision']['name'] ?? 'N/A' }}</td>
                <td><strong>District</strong></td>
                <td>{{ $students->district->district_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>State</strong></td>
                <td>{{ $students->state->state_name ?? 'N/A' }}</td>
                <td><strong>PIN No.</strong></td>
                <td>{{ $students->s_pin_no ?? 'N/A' }}</td>

            </tr>
        
    </table>
    @endif
    <table style="padding-top:10px;">
                <tr>
                    <th colspan="3" style="text-align:left; font-size:14px;">Reservation/Quota Details</th>
                </tr>
                <tr>
                        <td  style="width:40%;"><strong>Are you a Person with Disability(PwD)?</strong></td>
                        <td colspan="2">  {{ $students->s_pwd == 1 ? 'Yes' : 'No' }}

                                @if($students->s_pwd == 1)
                                    (
                                    <strong>Cert. No.:</strong> {{ $students->pc_cert_no ?? 'N/A' }},
                                    <strong>Date of issue:</strong> {{ \Carbon\Carbon::parse($students->pc_cert_date)->format('d-m-Y') }})</p>

                                @endif</td>
                    </tr>
        
                <tr>
                    <td  style="width:40%;"><strong>Caste</strong></td>
                    <td colspan="2">{{ $students->s_caste }}@if(strtolower($students->s_caste) !== 'general')
                            (
                            <strong>Cert. No.:</strong> {{ $students->cast_cert_number ?? 'N/A' }},
                            <strong>Date of issue:</strong> {{ $students->cast_cert_date ?? 'N/A' }},<br>
                            <strong>Sub-caste:</strong> {{ $students->cast_sub_category ?? 'N/A' }}
                            )
                        @endif</td>
                    </tr>
        
    </table>

    </div>

    <div style="page-break-before: always;">
                {{-- <div>
                    <p style="font-size:14px; font-weight:bold; padding-top:120px; white-space:nowrap;margin-left:100px;">
                        Application FormNum: {{ $students->s_appl_form_num }} (Polytechnic 1st year)
                    </p>
                </div> --}}
            <table style="padding-top:110px; width:100%; border-collapse:collapse;">
                <tr>
                    <td style="text-align:center;width:50%;">
                        <strong>Candidate's Name:</strong> {{ $students->s_candidate_name ?? 'N/A' }}
                    </td>
                    <td style="text-align:center;width:50%;">
                        <strong>Application Form No.:</strong> {{ $students->s_appl_form_num ?? 'N/A' }} 
                    </td>
                </tr>
            </table>


            
                <table style="padding-top:3px; width:100%; border-collapse:collapse;">
                        <tr>
                            <th colspan="8" style="text-align:left; font-size:14px;">Qualification Details</th>
                        </tr>
                        <tr>
                            <td style="text-align: center"><strong>Qualification(10th)</strong></td>
                            <td style="text-align: center"><strong>10th School Name</strong></td>
                            <td style="text-align: center"><strong>10th School District</strong></td>
                            <td style="text-align: center"><strong>10th Year of Passing</strong></td>
                            <td style="text-align: center"><strong> 10th Board</strong></td>
                            <td style="text-align: center"><strong>Total 10th Aggregate Marks</strong></td>
                            <td style="text-align: center"><strong>Total 10th Marks Obtained</strong></td>
                            <td style="text-align: center"><strong>Overall % </strong><span style="font-size:10px;">(Rounded off upto 2 decimal places)</span></td>
                        </tr>

                        <tr>
                            <td style="text-align: center">{{ $education->exam_elgb_code_one }}</td>
                            <td style="text-align: center">{{ $education->exam_10th_school_name }}</td>
                            <td style="text-align: center">{{ $education->district10th->district_name ?? 'N/A' }}</td>
                            <td style="text-align: center">{{ $education->exam_10th_pass_yr }}</td>
                            <td style="text-align: center">{{ strtoupper($education->board10th->board_name) }}</td>
                            <td style="text-align: center">{{ $education->exam_10th_tot_marks ?? 'N/A' }}</td>
                            <td style="text-align: center">{{ $education->exam_10th_ob_marks ?? 'N/A' }}</td>
                            <td style="text-align: center">{{ $education->exam_10th_percentage ?? 'N/A' }}
                               
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: center"><strong>Qualification(12th)</strong></td>
                            <td style="text-align: center"><strong>12th School Name</strong></td>
                            <td style="text-align: center"><strong>12th School District</strong></td>
                            <td style="text-align: center"><strong>12th Year of Passing</strong></td>
                            <td style="text-align: center"><strong> 12th Board</strong></td>
                            <td style="text-align: center"><strong>Total 12th Aggregate Marks</strong></td>
                            <td style="text-align: center"><strong>Total 12th Marks Obtained</strong></td>
                            <td style="text-align: center"><strong>Overall % </strong><span style="font-size:10px;">(Rounded off upto 2 decimal places)</span></td>
                        </tr>

                        <tr>
                            <td style="text-align: center">{{ $education->exam_elgb_code_two }}</td>
                            <td style="text-align: center">{{ $education->exam_12th_school_name }}</td>
                            <td style="text-align: center">{{ $education->district12th->district_name ?? 'N/A' }}</td>
                            <td style="text-align: center">{{ $education->exam_12th_pass_yr }}</td>
                            <td style="text-align: center">{{ strtoupper($education->board12th->board_name) }}</td>
                            <td style="text-align: center">{{ $education->exam_12th_tot_marks ?? 'N/A' }}</td>
                            <td style="text-align: center">{{ $education->exam_12th_ob_marks ?? 'N/A' }}</td>
                            <td style="text-align: center">{{ $education->exam_12th_percentage ?? 'N/A' }}
                            </td>
                        </tr>

            {{-- Subject-wise marks --}}
                    @if(!empty($subjects) && is_array($subjects))
                        <tr>
                            <td colspan="8">
                                <table cellpadding="4" style="width:100%; margin-top:5px; border-collapse:collapse;">
                                    <tr>
                                        <th style="text-align: center">Subject</th>
                                        <th style="text-align: center">Full Marks</th>
                                        <th style="text-align: center">Marks Obtained </th>
                                        <th style="text-align: center">Percentage</th>
                                    </tr>
                                    
                                    @foreach($subjects as $sub)
                                    @if(!empty($sub['total']) && !empty($sub['obtained']))
                                        <tr>
                                            <td style="text-align: center">{{ $sub['subject'] }}</td>
                                            <td style="text-align: center">{{ $sub['total'] }}</td>
                                            <td style="text-align: center">{{ $sub['obtained'] }}</td>
                                            <td style="text-align: center">
                                                @if(!empty($sub['total']) && $sub['total'] > 0)
                                                    {{ number_format(($sub['obtained'] / $sub['total']) * 100, 2) }}%
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach

                                </table>
                            </td>
                        </tr>
                    @endif
                </table>
                <table>
                    <tr>
                        <th colspan="5" style="text-align:left; font-size:14px;">Bank Account Details for refund of successfull duplicate payment (if any) made by the applicant</th>
                    </tr>
                    {{-- <h3><strong>Bank Details For Refund fee:</strong></h3> --}}
                    <tr>
                        <td style="text-align: center"><strong>Account Holder Name</strong></td>
                        <td style="text-align: center"><strong>Account Number</strong></td>
                        <td style="text-align: center"><strong>IFSC</strong></td>
                         <td style="text-align: center"><strong>Bank name</strong></td>
                        <td style="text-align: center"><strong>Bank Branch name</strong></td>
                        

                    </tr>
                    <tr>
                         <td style="text-align: center">{{ $bank_details['accHolderName'] ?? 'N/A' }}</td>
                         <td style="text-align: center">{{ $bank_details['accNumber'] ?? 'N/A' }}</td>
                        <td style="text-align: center">{{ $bank_details['IFSC'] ?? 'N/A' }}</td>
                        <td style="text-align: center">{{ $bank_details['bankName'] ?? 'N/A' }}</td>
                        <td style="text-align: center">{{ $bank_details['bankBranchName'] ?? 'N/A' }}</td>


                    </tr>
                    
                </table>
                @if($type != 'PREVIEW')
                      <table >
                    <tr>
                        <th colspan="5" style="text-align:left; font-size:14px;">Payment Details</th>
                    </tr>
                    <tr>
                        <td><strong>Payment mode</strong></td>
                    
                    
                        <td style="text-align: center"><strong>Merchant Order No.</strong></td>
                        <td style="text-align: center"><strong>Payment Date</strong></td>
                        <td style="text-align: center"><strong>Transaction ID</strong></td>
                        <td style="text-align: center"><strong>Transaction Amount (Rs.)</strong></td>

                    </tr>
                    <tr>
                        <td style="text-align: center">{{ $payment->trans_mode ?? 'N/A'}}</td>

                        <td style="text-align: center">{{ $payment->marchnt_id ?? 'N/A'}}</td>

                        <td style="text-align: center">{{ $payment->trans_time ?? 'N/A'}}</td>
                        <td style="text-align: center">{{ $payment->trans_id ?? 'N/A'}}</td>
                        <td style="text-align: center">{{ $payment->trans_amount ?? 'N/A'}}</td>

                    </tr>
                
                </table>

                @endif
              
                <div style="position: fixed; bottom: 15px; left: 20px; right: 20px; font-size:12px; line-height:1.3;">

                    <h3 style="margin:0; padding:0; font-size:12px;">
                        <strong>Declaration:</strong>
                    </h3>

                    <p style="margin:3px 0; text-align: justify; font-size:11px; line-height:1.3;">
                        I do hereby declare that the information provided by me hereinbefore is true to the best of my knowledge & belief and nothing has been concealed. I understand that submission of any false/incorrect information may lead to cancellation of my candidature from the admission process at any point of time, even after securing a position by me in the merit list, allotment of seat in my favour, taking provisional admission in any polytechnic and getting registered under the WBSCT&VE&SD.
                    </p>

                    <table style="width:100%; border:none; border-collapse:collapse; margin-top:6px;">
                        <tr style="border:none;">
                            {{-- Left side --}}
                            <td style="width:50%; text-align:left; vertical-align:middle; border:none; font-size:11px;">
                                <strong>Place:</strong> {{ $students->s_address ?? 'N/A' }}<br>
                              <strong>Date & Time:</strong> {{ date('d-m-Y H:i:s') }}

                            </td>

                            {{-- Right side --}}
                            <td style="width:50%; text-align:right; vertical-align:middle; border:none;">
                                @if(!empty($students->s_sign))
                                    <img src="{{ public_path('storage/' . $students->s_sign) }}" 
                                        alt="Signature" 
                                        style="width:120px; height:auto;">
                                @else
                                    <span style="font-size:11px;">No Signature Provided</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                </div>


        </div>


   


</body>
</html>
