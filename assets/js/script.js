// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Seat selection functionality
let selectedSeat = null;

function selectSeat(seatNumber, isBooked) {
    if (isBooked) {
        return;
    }

    // Remove previous selection
    const previouslySelected = document.querySelector('.seat-selected');
    if (previouslySelected) {
        previouslySelected.classList.remove('seat-selected');
    }

    // Select new seat
    const seatElement = document.querySelector(`[data-seat="${seatNumber}"]`);
    seatElement.classList.add('seat-selected');
    selectedSeat = seatNumber;

    // Update hidden input
    const seatInput = document.getElementById('seat_number');
    if (seatInput) {
        seatInput.value = seatNumber;
    }

    // Update total price display if exists
    updateTotalPrice();
}

function updateTotalPrice() {
    const priceElement = document.getElementById('trip_price');
    const totalElement = document.getElementById('total_price');
    const couponDiscountElement = document.getElementById('coupon_discount');

    if (priceElement && totalElement) {
        const basePrice = parseFloat(priceElement.value);
        const discount = couponDiscountElement ? parseFloat(couponDiscountElement.value) : 0;
        const totalPrice = basePrice - (basePrice * discount / 100);

        const totalDisplay = document.getElementById('total_price_display');
        if (totalDisplay) {
            totalDisplay.textContent = totalPrice.toFixed(2) + ' TL';
        }
    }
}

// Coupon validation
async function applyCoupon() {
    const couponCode = document.getElementById('coupon_code').value.trim();
    const tripId = document.getElementById('trip_id').value;

    if (!couponCode) {
        showAlert('Lütfen kupon kodu girin', 'warning');
        return;
    }

    try {
        const response = await fetch('/api/validate_coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `coupon_code=${encodeURIComponent(couponCode)}&trip_id=${encodeURIComponent(tripId)}`
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('coupon_discount').value = data.discount;
            document.getElementById('coupon_id').value = data.coupon_id;
            updateTotalPrice();
            showAlert(`Kupon uygulandı! %${data.discount} indirim kazandınız.`, 'success');
        } else {
            showAlert(data.message || 'Geçersiz kupon kodu', 'danger');
        }
    } catch (error) {
        showAlert('Kupon doğrulanırken bir hata oluştu', 'danger');
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('main.container');
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Bu işlemi gerçekleştirmek istediğinizden emin misiniz?');
}
