<?php
/**
 * Payment Controller
 * Handles payment operations and management
 */
class PaymentController extends Controller {
    private $paymentModel;
    private $saleModel;
    private $installmentModel;
    private $bankAccountModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->paymentModel = $this->model('Payment');
        $this->saleModel = $this->model('Sale');
        $this->installmentModel = $this->model('Installment');
        $this->bankAccountModel = $this->model('BankAccount');
    }

    /**
     * Record payment
     */
    public function record() {
        if (!$this->isPost()) {
            $this->redirect('sales');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('sales');
            return;
        }

        $saleId = $this->getPost('sale_id');
        $data = [
            'sale_id' => $saleId,
            'amount' => floatval($this->getPost('amount')),
            'payment_method' => $this->getPost('payment_method'),
            'payment_date' => $this->getPost('payment_date', date('Y-m-d')),
            'reference_number' => $this->getPost('reference_number'),
            'notes' => trim($this->getPost('notes'))
        ];

        // Add bank account if payment method is bank
        if ($data['payment_method'] === 'bank') {
            $data['bank_account_id'] = $this->getPost('bank_account_id');
            if (empty($data['bank_account_id'])) {
                $this->setFlash('error', 'Please select a bank account');
                $this->redirect("sales/view/{$saleId}");
                return;
            }
        }

        // Add installment ID if paying for specific installment
        $installmentId = $this->getPost('installment_id');
        if ($installmentId) {
            $data['installment_id'] = $installmentId;
        }

        // Create payment
        if ($this->paymentModel->createPayment($data)) {
            $this->logActivity('payment', "Recorded payment for sale #{$saleId}");
            $this->setFlash('success', 'Payment recorded successfully');
        } else {
            $this->setFlash('error', 'Failed to record payment');
        }

        $this->redirect("sales/view/{$saleId}");
    }

    /**
     * Process installment payment
     */
    public function processInstallment() {
        if (!$this->isPost()) {
            $this->redirect('sales');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('sales');
            return;
        }

        $installmentId = $this->getPost('installment_id');
        $data = [
            'payment_method' => $this->getPost('payment_method'),
            'payment_date' => $this->getPost('payment_date', date('Y-m-d')),
            'reference_number' => $this->getPost('reference_number'),
            'notes' => trim($this->getPost('notes'))
        ];

        if ($data['payment_method'] === 'bank') {
            $data['bank_account_id'] = $this->getPost('bank_account_id');
            if (empty($data['bank_account_id'])) {
                $this->setFlash('error', 'Please select a bank account');
                $this->redirect('sales');
                return;
            }
        }

        if ($this->installmentModel->processPayment($installmentId, $data)) {
            $this->logActivity('payment', "Processed installment payment #{$installmentId}");
            $this->setFlash('success', 'Installment payment processed successfully');
        } else {
            $this->setFlash('error', 'Failed to process installment payment');
        }

        $this->redirect('sales');
    }

    /**
     * Show payment details
     */
    public function view($id = null) {
        if (!$id) {
            $this->redirect('sales');
            return;
        }

        $payment = $this->paymentModel->getById($id);
        if (!$payment) {
            $this->setFlash('error', 'Payment not found');
            $this->redirect('sales');
            return;
        }

        $this->view->render('payments/view', [
            'title' => "Payment Details - " . APP_NAME,
            'payment' => $payment
        ]);
    }

    /**
     * Show overdue installments
     */
    public function overdue() {
        $installments = $this->installmentModel->getOverdue();
        $summary = $this->installmentModel->getSummary();
        $bankAccounts = $this->bankAccountModel->getActive();

        $this->view->render('payments/overdue', [
            'title' => 'Overdue Installments - ' . APP_NAME,
            'installments' => $installments,
            'summary' => $summary,
            'bankAccounts' => $bankAccounts,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show upcoming installments
     */
    public function upcoming() {
        $days = $this->getQuery('days', 7);
        $installments = $this->installmentModel->getUpcoming($days);
        $bankAccounts = $this->bankAccountModel->getActive();

        $this->view->render('payments/upcoming', [
            'title' => 'Upcoming Installments - ' . APP_NAME,
            'installments' => $installments,
            'days' => $days,
            'bankAccounts' => $bankAccounts,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show bank accounts
     */
    public function bankAccounts() {
        $this->requireAdmin();

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));
        
        $summary = $this->bankAccountModel->getTransactionSummary($startDate, $endDate);
        $bankAccounts = $this->bankAccountModel->getActive();

        $this->view->render('payments/bank_accounts', [
            'title' => 'Bank Accounts - ' . APP_NAME,
            'summary' => $summary,
            'bankAccounts' => $bankAccounts,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Create bank account
     */
    public function createBankAccount() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('payments/bankAccounts');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('payments/bankAccounts');
            return;
        }

        $data = [
            'bank_name' => $this->getPost('bank_name'),
            'account_number' => $this->getPost('account_number'),
            'account_name' => $this->getPost('account_name'),
            'branch' => $this->getPost('branch'),
            'status' => 'active'
        ];

        if ($this->bankAccountModel->createWithValidation($data)) {
            $this->logActivity('bank_account', "Created bank account: {$data['bank_name']} - {$data['account_number']}");
            $this->setFlash('success', 'Bank account created successfully');
        } else {
            $this->setFlash('error', $this->bankAccountModel->getError() ?? 'Failed to create bank account');
        }

        $this->redirect('payments/bankAccounts');
    }

    /**
     * Update bank account
     */
    public function updateBankAccount($id = null) {
        $this->requireAdmin();

        if (!$this->isPost() || !$id) {
            $this->redirect('payments/bankAccounts');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('payments/bankAccounts');
            return;
        }

        $data = [
            'bank_name' => $this->getPost('bank_name'),
            'account_number' => $this->getPost('account_number'),
            'account_name' => $this->getPost('account_name'),
            'branch' => $this->getPost('branch'),
            'status' => $this->getPost('status')
        ];

        if ($this->bankAccountModel->updateWithValidation($id, $data)) {
            $this->logActivity('bank_account', "Updated bank account #{$id}");
            $this->setFlash('success', 'Bank account updated successfully');
        } else {
            $this->setFlash('error', $this->bankAccountModel->getError() ?? 'Failed to update bank account');
        }

        $this->redirect('payments/bankAccounts');
    }
}
