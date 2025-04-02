// Show and hide the wallet popup
const walletLink = document.getElementById('walletLink');
const walletPopup = document.getElementById('walletPopup');
const closeWalletPopup = document.getElementById('closeWalletPopup');

if (walletLink) {
    walletLink.addEventListener('click', (e) => {
        e.preventDefault();
        walletPopup.style.display = 'block';
        fetchWalletBalance(); // Fetch and display the wallet balance when the popup is opened
    });
}

if (closeWalletPopup) {
    closeWalletPopup.addEventListener('click', () => {
        walletPopup.style.display = 'none';
    });
}

// Fetch wallet balance on page load and for the popup
async function fetchWalletBalance() {
    try {
        const response = await fetch('recharge_wallet.php?action=get_balance');
        const result = await response.json();
        if (result.status === 'success') {
            // Ensure the balance is a number
            const balance = parseFloat(result.balance);
            if (!isNaN(balance)) {
                // Update wallet balance in the wallet popup
                const walletBalanceElement = document.getElementById('walletBalance');
                if (walletBalanceElement) {
                    walletBalanceElement.textContent = balance.toFixed(2);
                }

                // Update wallet balance in the navbar
                const navbarWalletBalanceElement = document.getElementById('navbarWalletBalance');
                if (navbarWalletBalanceElement) {
                    navbarWalletBalanceElement.textContent = balance.toFixed(2);
                }
            } else {
                throw new Error('Invalid balance value');
            }
        } else {
            console.error('Error fetching wallet balance:', result.message);
            const walletBalanceElement = document.getElementById('walletBalance');
            if (walletBalanceElement) {
                walletBalanceElement.textContent = 'Error';
            }

            const navbarWalletBalanceElement = document.getElementById('navbarWalletBalance');
            if (navbarWalletBalanceElement) {
                navbarWalletBalanceElement.textContent = 'Error';
            }
        }
    } catch (error) {
        console.error('Error fetching wallet balance:', error);
    }
}

// Handle wallet recharge
const rechargeForm = document.getElementById('rechargeWalletForm');
const rechargeMessage = document.getElementById('rechargeMessage');

if (rechargeForm) {
    rechargeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(rechargeForm);
        const response = await fetch('recharge_wallet.php?action=recharge', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
            rechargeMessage.innerHTML = `<p style="color: green;">${result.message}</p>`;
            fetchWalletBalance(); // Update wallet balance after recharge
        } else {
            rechargeMessage.innerHTML = `<p style="color: red;">${result.message}</p>`;
        }
    });
}

// Call fetchWalletBalance on page load
fetchWalletBalance();