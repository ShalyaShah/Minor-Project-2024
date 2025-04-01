document.getElementById('payment-method').addEventListener('change', function() {
    const selectedMethod = this.value;
    const paymentFields = document.querySelectorAll('.payment-fields');

    // Hide all payment fields initially
    paymentFields.forEach(field => {
        field.classList.add('hidden');
    });

    // Show the selected payment method fields
    if (selectedMethod) {
        document.getElementById(`${selectedMethod}-fields`).classList.remove('hidden');
    }
});

document.getElementById('payment-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent form submission for validation

    const selectedMethod = document.getElementById('payment-method').value;

    // Show loading spinner
    document.getElementById('loading').classList.remove('hidden');
    document.getElementById('payment-form').classList.add('hidden');

    // Simulate payment processing (replace with actual payment logic)
    setTimeout(() => {
        // Validate based on selected payment method
        let isValid = true;
        let errorMessage = '';

        switch (selectedMethod) {
            case 'credit-card':
                isValid = validateCreditCard();
                errorMessage = isValid ? '' : 'Please fill in all credit card fields correctly.';
                break;
            case 'paypal':
                isValid = validatePayPal();
                errorMessage = isValid ? '' : 'Please enter a valid PayPal email.';
                break;
            case 'bank-transfer':
                isValid = validateBankTransfer();
                errorMessage = isValid ? '' : 'Please fill in all bank transfer fields correctly.';
                break;
            case 'upi':
                isValid = validateUPI();
                errorMessage = isValid ? '' : 'Please enter a valid UPI ID.';
                break;
            default:
                errorMessage = 'Please select a payment method.';
                isValid = false;
        }

        if (!isValid) {
            // Hide loading spinner and show the form again
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('payment-form').classList.remove('hidden');
            alert(errorMessage);
        } else {
            // Hide loading spinner and show success message
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('success-message').classList.remove('hidden');
        }
    }, 2000); // Simulate a 2-second processing time
});

function validateCreditCard() {
    const cardName = document.getElementById('card-name').value.trim();
    const cardNumber = document.getElementById('card-number').value.trim();
    const expiryDate = document.getElementById('expiry-date').value.trim();
    const cvv = document.getElementById('cvv').value.trim();

    const cardNumberPattern = /^\d{16}$/; // Simple pattern for 16-digit card number
    const expiryDatePattern = /^(0[1-9]|1[0-2])\/?([0-9]{2})$/; // MM/YY format
    const cvvPattern = /^\d{3}$/; // 3-digit CVV

    return cardName && cardNumberPattern.test(cardNumber) && expiryDatePattern.test(expiryDate) && cvvPattern.test(cvv);
}

function validatePayPal() {
    const paypalEmail = document.getElementById('paypal-email').value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // Basic email pattern
    return emailPattern.test(paypalEmail);
}

function validateBankTransfer() {
    const accountNumber = document.getElementById('account-number').value.trim();
    const ifscCode = document.getElementById('ifsc-code').value.trim();
    const accountNumberPattern = /^\d{10}$/; // Simple pattern for 10-digit account number
    const ifscCodePattern = /^[A-Z]{4}0[A-Z0-9]{6}$/; // Basic IFSC code pattern

    return accountNumberPattern.test(accountNumber) && ifscCodePattern.test(ifscCode);
}

function validateUPI() {
    const upiId = document.getElementById('upi-id').value.trim();
    // Basic UPI ID pattern (example: example@upi)
    const upiPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+$/;
    return upiPattern.test(upiId);
}
function addPaymentFormListeners() {
    document.getElementById('payment-method').addEventListener('change', function() {
        const selectedMethod = this.value;
        const paymentFields = document.querySelectorAll('.payment-fields');

        paymentFields.forEach(field => field.classList.add('hidden'));

        if (selectedMethod) {
            document.getElementById(`${selectedMethod}-fields`).classList.remove('hidden');
        }
    });

    document.getElementById('payment-form').addEventListener('submit', function(event) {
        event.preventDefault();

        const selectedMethod = document.getElementById('payment-method').value;

        document.getElementById('loading').classList.remove('hidden');
        document.getElementById('payment-form').classList.add('hidden');

        setTimeout(() => {
            let isValid = true;
            let errorMessage = '';

            switch (selectedMethod) {
                case 'credit-card':
                    isValid = validateCreditCard();
                    errorMessage = isValid ? '' : 'Please fill in all credit card fields correctly.';
                    break;
                case 'paypal':
                    isValid = validatePayPal();
                    errorMessage = isValid ? '' : 'Please enter a valid PayPal email.';
                    break;
                case 'bank-transfer':
                    isValid = validateBankTransfer();
                    errorMessage = isValid ? '' : 'Please fill in all bank transfer fields correctly.';
                    break;
                case 'upi':
                    isValid = validateUPI();
                    errorMessage = isValid ? '' : 'Please enter a valid UPI ID.';
                    break;
                default:
                    errorMessage = 'Please select a payment method.';
                    isValid = false;
            }

            if (!isValid) {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('payment-form').classList.remove('hidden');
                alert(errorMessage);
            } else {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('success-message').classList.remove('hidden');
            }
        }, 2000);
    });
}
