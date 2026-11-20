<?php

namespace Database\Seeders;

use App\Models\FaqEntry;
use Illuminate\Database\Seeder;

/**
 * Starter answers for the help assistant, in English and Tagalog. Safe to run
 * anywhere and more than once (it updates by question). IMPORTANT: the school
 * must read and correct these before go-live. They describe how this portal
 * works; dates, fees and office hours are not included on purpose because only
 * the school knows them. `guide` is the step-by-step guide (config/guides.php)
 * the assistant starts when this entry matches.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        // [category, question, question_tl, keywords, answer, answer_tl, guide]
        $faqs = [
            ['enrollment', 'What do I need to enroll my child in Grade 7?', 'Ano ang kailangan para i-enroll ang anak ko sa Grade 7?',
                'enroll enrollment grade 7 grade seven new student requirements documents papers needed mag-enroll magpa-enroll kailangan requirement papeles',
                'Create an account, open "Enrollment" and fill in the online form. Upload a PSA birth certificate and the report card (Form 138). The registrar reviews the form and you get an email with the decision.',
                'Gumawa ng account, buksan ang "Enrollment" at punan ang online form. I-upload ang PSA birth certificate at report card (Form 138). Rerepasuhin ito ng registrar at makakatanggap ka ng email tungkol sa desisyon.',
                'enroll'],
            ['enrollment', 'What do transferees need to enroll?', 'Ano ang kailangan ng lilipat o transferee para mag-enroll?',
                'transferee transfer lipat lilipat transferring from another school previous school good moral requirements kailangan requirement papeles mag-enroll',
                'Transferees fill in the same online form, choose the grade they will enter and write their previous school. They upload the PSA birth certificate, the report card and a certificate of good moral character.',
                'Parehong online form ang pupunan ng transferee. Piliin ang grade na papasukan at isulat ang dating school. I-upload ang PSA birth certificate, report card at certificate of good moral character.',
                'enroll'],
            ['enrollment', 'How do I know if my enrollment was approved?', 'Paano ko malalaman kung na-approve ang enrollment?',
                'approved status result decision track follow up check enrollment status email natanggap na-approve',
                'Open "Enrollment" after you log in to see the status of your form. You also get an email when the registrar approves or rejects it. If it is rejected, the reason is shown on the form.',
                'Buksan ang "Enrollment" pagkatapos mag-log in para makita ang status ng form. May email ka rin kapag inaprubahan o tinanggihan ito ng registrar. Kung tinanggihan, nakasulat ang dahilan sa form.',
                null],
            ['enrollment', 'Can I enroll without an LRN?', 'Puwede bang mag-enroll kahit walang LRN?',
                'lrn learner reference number wala walang lrn no lrn',
                'Yes. The LRN field is optional. If your child already has an LRN, please type it so the registrar can find the existing record.',
                'Opo. Optional ang LRN. Kung may LRN na ang anak mo, ilagay ito para mahanap ng registrar ang dati niyang record.',
                null],
            ['account', 'How do I create an account?', 'Paano gumawa ng account?',
                'register sign up create account new account mag-register magparehistro gumawa account magsign up',
                'Press "Register" on the home page, type your name, email and a password, then press "Create account".',
                'Pindutin ang "Register" sa home page, ilagay ang pangalan, email at password, tapos pindutin ang "Create account".',
                'register'],
            ['account', 'How do I log in?', 'Paano mag-log in?',
                'login log in sign in enter pumasok mag-login magsign in pasok',
                'Press "Log in" on the home page, type your email and password, then press "Log in".',
                'Pindutin ang "Log in" sa home page, ilagay ang email at password, tapos pindutin ang "Log in".',
                'login'],
            ['account', 'How do I see my child\'s school records?', 'Paano makikita ang school records ng anak ko?',
                'link account records view see record student parent magulang anak records tingnan makita i-link',
                'Log in, open "My records" and enter the learner\'s LRN and birthdate. The registrar approves the link, then the record appears in your account.',
                'Mag-log in, buksan ang "My records" at ilagay ang LRN at birthdate ng learner. Aaprubahan ng registrar ang link, tapos lalabas ang record sa account mo.',
                'link_record'],
            ['documents', 'How do I request Form 137?', 'Paano humingi ng Form 137?',
                'form 137 permanent record request form137 transfer records kuha humingi magrequest mag-request',
                'Link the learner\'s record to your account first. Then open "My requests", choose "Form 137", write the purpose and send. You can follow the status of the request and get an email at every step.',
                'I-link muna ang record ng learner sa account mo. Tapos buksan ang "My requests", piliin ang "Form 137", isulat ang dahilan at ipadala. Masusubaybayan mo ang status at may email sa bawat hakbang.',
                'request_document'],
            ['documents', 'How do I request a diploma or certificate of graduation?', 'Paano humingi ng diploma o certificate of graduation?',
                'diploma certificate graduation graduate certification request alumni nagtapos humingi',
                'Open "My requests", choose "Diploma / certificate of graduation" and send the request. Alumni can use the same steps after the registrar approved the link to their record.',
                'Buksan ang "My requests", piliin ang "Diploma / certificate of graduation" at ipadala ang request. Ganito rin ang alumni pagkatapos aprubahan ng registrar ang link sa record nila.',
                'request_document'],
            ['documents', 'How long does a document request take?', 'Gaano katagal ang pagproseso ng request?',
                'how long processing time days wait waiting gaano katagal tagal matagal duration track status follow up',
                'The registrar reviews every request. You will see the status change in your account and receive an email at each step: received, approved, ready, released. For the usual number of days, please ask the registrar\'s office.',
                'Rerepasuhin ng registrar ang bawat request. Makikita mo ang pagbabago ng status sa account mo at may email sa bawat hakbang: received, approved, ready, released. Para sa karaniwang bilang ng araw, magtanong sa opisina ng registrar.',
                'track_request'],
            ['documents', 'How do I download my document?', 'Paano i-download ang document ko?',
                'download pdf print get document released ready kunin i-download',
                'When the registrar releases the document, open the request and press "Download PDF". The link works for two minutes and you must be logged in. Press the button again if it expired.',
                'Kapag na-release na ng registrar ang document, buksan ang request at pindutin ang "Download PDF". Dalawang minuto lang gumagana ang link at kailangang naka-log in ka. Pindutin ulit ang button kung nag-expire.',
                'track_request'],
            ['documents', 'How can I check if a document is genuine?', 'Paano malalaman kung totoo ang document?',
                'verify verification genuine fake real authentic qr code scan peke totoo',
                'Every document has a QR code and a request number. Scan the QR code to open a page that tells you if the document was issued by the school. The page hides the learner\'s full name for privacy.',
                'May QR code at request number ang bawat document. I-scan ang QR code para makita kung inilabas ito ng school. Nakatago ang buong pangalan ng learner para sa privacy.',
                null],
            ['account', 'I forgot my password.', 'Nakalimutan ko ang password ko.',
                'forgot password reset login cannot login nakalimutan password hindi makapasok makalimutan',
                'On the login page choose "Forgot password" and type your email address. You will get a link to set a new password.',
                'Sa login page, piliin ang "Forgot password" at ilagay ang email. Makakatanggap ka ng link para sa bagong password.',
                'forgot_password'],
            ['account', 'Why does my document request say rejected?', 'Bakit tinanggihan ang request ko?',
                'rejected denied declined reason hindi approved bakit tinanggihan',
                'Open the request to read the note from the registrar. If something was missing, you can send a new request after fixing it.',
                'Buksan ang request para mabasa ang tala ng registrar. Kung may kulang, puwede kang magpadala ng bagong request pagkatapos itong ayusin.',
                'track_request'],
            ['contact', 'How can I contact the school?', 'Paano makikipag-ugnayan sa school?',
                'contact call phone number mobile email address location where saan matatagpuan tawag makipag-ugnayan cellphone landline facebook page office',
                'Cawayan National High School is in Cawayan, Bongabong, Oriental Mindoro 5211. Mobile: 0963 783 4433. Email: 301624@deped.gov.ph. You can also message the official Facebook page, DepEd Cawayan National High School.',
                'Nasa Cawayan, Bongabong, Oriental Mindoro 5211 ang Cawayan National High School. Mobile: 0963 783 4433. Email: 301624@deped.gov.ph. Puwede ring mag-message sa official Facebook page na DepEd Cawayan National High School.',
                null],
            ['privacy', 'Is my information safe?', 'Ligtas ba ang impormasyon ko?',
                'privacy safe security data personal information ligtas data privacy act ra 10173 seguridad',
                'The school keeps student records private. Only you (and the registrar staff who need them) can see a linked record, files are not public, and every view and download is logged. See the school for the full privacy notice.',
                'Pribado ang student records. Ikaw lang (at ang registrar staff na nangangailangan) ang makakakita ng naka-link na record, hindi public ang mga file, at nakatala ang bawat pagtingin at pag-download. Humingi sa school ng buong privacy notice.',
                null],
            ['staff', 'How do I find a learner?', 'Paano maghanap ng learner?',
                'search find learner student lrn name hanap hanapin maghanap records typo',
                'Open "Student records" and type the LRN or the name in the search box. Small spelling mistakes are fine.',
                'Buksan ang "Student records" at ilagay ang LRN o pangalan sa search box. Okay lang ang maliliit na mali sa spelling.',
                'search_student'],
            ['staff', 'How do I process a document request?', 'Paano iproseso ang document request?',
                'process review approve release request registrar staff iproseso i-release i-approve',
                'Open "Document requests", click a request, then use Start review, Approve and Release in order.',
                'Buksan ang "Document requests", i-click ang request, tapos gamitin ang Start review, Approve at Release ayon sa pagkakasunod.',
                'process_request'],
            ['staff', 'How do I approve an enrollment form?', 'Paano mag-approve ng enrollment form?',
                'approve reject enrollment form registrar staff section i-approve i-reject',
                'Open "Enrollments", click a form, check the documents, then Approve (you can choose a section) or Reject with a reason.',
                'Buksan ang "Enrollments", i-click ang form, tingnan ang mga document, tapos i-Approve (puwedeng pumili ng section) o i-Reject na may dahilan.',
                'approve_enrollment'],
            ['staff', 'How do I add a class section?', 'Paano magdagdag ng section?',
                'section add create class adviser grade strand magdagdag gumawa section',
                'Open "Class sections", press "Add section" in the grade you want, then type the name, adviser and size.',
                'Buksan ang "Class sections", pindutin ang "Add section" sa grade na gusto mo, tapos ilagay ang pangalan, adviser at laki.',
                'add_section'],
            ['staff', 'How do I digitize an old paper record?', 'Paano gawing digital ang lumang papel na record?',
                'scan ocr digitize old paper record upload photo picture lumang scan larawan',
                'Open "Scan records", press "Upload a scan", choose the picture and check every field before you save. The scan only suggests; you confirm.',
                'Buksan ang "Scan records", pindutin ang "Upload a scan", piliin ang larawan at suriin ang bawat field bago i-save. Mungkahi lang ang scan; ikaw ang kumukumpirma.',
                'digitize'],
        ];

        foreach ($faqs as $i => [$category, $question, $questionTl, $keywords, $answer, $answerTl, $guide]) {
            FaqEntry::updateOrCreate(
                ['question' => $question],
                [
                    'category' => $category, 'question_tl' => $questionTl, 'keywords' => $keywords, 'answer' => $answer,
                    'answer_tl' => $answerTl, 'guide' => $guide, 'is_active' => true, 'sort' => $i,
                ],
            );
        }
    }
}
