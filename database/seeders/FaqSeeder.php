<?php

namespace Database\Seeders;

use App\Models\FaqEntry;
use Illuminate\Database\Seeder;

/**
 * Starter answers for the FAQ chatbot. Safe to run anywhere and more than once
 * (it updates by question). IMPORTANT: the school must read and correct these
 * before go-live. They describe how this portal works; dates, fees and office
 * hours are not included on purpose because only the school knows them.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['enrollment', 'What do I need to enroll my child in Grade 7?',
                'enroll enrollment grade 7 grade seven new student requirements documents papers needed mag-enroll magpa-enroll kailangan requirement',
                'Create an account, open "Enrollment" and fill in the online form. Upload a PSA birth certificate and the report card (Form 138). The registrar reviews the form and you get an email with the decision.'],
            ['enrollment', 'What do transferees need to enroll?',
                'transferee transfer lipat transferring from another school previous school good moral requirements kailangan requirement papeles mag-enroll',
                'Transferees fill in the same online form, choose the grade they will enter and write their previous school. They upload the PSA birth certificate, the report card and a certificate of good moral character.'],
            ['enrollment', 'How do I know if my enrollment was approved?',
                'approved status result decision track follow up check enrollment status email natanggap',
                'Open "Enrollment" after you log in to see the status of your form. You also get an email when the registrar approves or rejects it. If it is rejected, the reason is shown on the form.'],
            ['enrollment', 'Can I enroll without an LRN?',
                'lrn learner reference number wala walang lrn no lrn',
                'Yes. The LRN field is optional. If your child already has an LRN, please type it so the registrar can find the existing record.'],
            ['account', 'How do I see my child\'s school records?',
                'link account records view see record student parent magulang anak records tingnan',
                'Log in, open "My records" and enter the learner\'s LRN and birthdate. The registrar approves the link, then the record appears in your account.'],
            ['documents', 'How do I request Form 137?',
                'form 137 permanent record request form137 transfer records kuha humingi',
                'Link the learner\'s record to your account first. Then open "My requests", choose "Form 137", write the purpose and send. You can follow the status of the request and get an email at every step.'],
            ['documents', 'How do I request a diploma or certificate of graduation?',
                'diploma certificate graduation graduate certification request alumni nagtapos',
                'Open "My requests", choose "Diploma / certificate of graduation" and send the request. Alumni can use the same steps after the registrar approved the link to their record.'],
            ['documents', 'How long does a document request take?',
                'how long processing time days wait waiting gaano katagal tagal matagal duration',
                'The registrar reviews every request. You will see the status change in your account and receive an email at each step: received, approved, ready, released. For the usual number of days, please ask the registrar\'s office.'],
            ['documents', 'How do I download my document?',
                'download pdf print get document released ready kunin i-download',
                'When the registrar releases the document, open the request and press "Download PDF". The link works for two minutes and you must be logged in. Press the button again if it expired.'],
            ['documents', 'How can I check if a document is genuine?',
                'verify verification genuine fake real authentic qr code scan peke totoo',
                'Every document has a QR code and a request number. Scan the QR code to open a page that tells you if the document was issued by the school. The page hides the learner\'s full name for privacy.'],
            ['account', 'I forgot my password.',
                'forgot password reset login cannot login nakalimutan password hindi makapasok',
                'On the login page choose "Forgot password" and type your email address. You will get a link to set a new password.'],
            ['account', 'Why does my document request say rejected?',
                'rejected denied declined reason hindi approved bakit tinanggihan',
                'Open the request to read the note from the registrar. If something was missing, you can send a new request after fixing it.'],
            ['contact', 'How can I contact the school?',
                'contact call phone number mobile email address location where saan matatagpuan tawag makipag-ugnayan cellphone landline facebook page office',
                'Cawayan National High School is in Cawayan, Bongabong, Oriental Mindoro 5211. Mobile: 0963 783 4433. Email: 301624@deped.gov.ph. You can also message the official Facebook page, DepEd Cawayan National High School.'],
            ['privacy', 'Is my information safe?',
                'privacy safe security data personal information ligtas data privacy act ra 10173',
                'The school keeps student records private. Only you (and the registrar staff who need them) can see a linked record, files are not public, and every view and download is logged. See the school for the full privacy notice.'],
        ];

        foreach ($faqs as $i => [$category, $question, $keywords, $answer]) {
            FaqEntry::updateOrCreate(
                ['question' => $question],
                ['category' => $category, 'keywords' => $keywords, 'answer' => $answer, 'is_active' => true, 'sort' => $i],
            );
        }
    }
}
