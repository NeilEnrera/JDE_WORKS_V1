/* reviews.js - Product Review Interactivity */

document.addEventListener('DOMContentLoaded', function () {
    loadReviews();
    setupReviewForms();
});

function toggleReviewForm() {
    const container = document.getElementById('reviewFormContainer');
    if (!container) return;

    if (container.style.display === 'none') {
        container.style.display = 'block';
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        container.style.display = 'none';
    }
}

function setupReviewForms() {
    const form = document.getElementById('productReviewForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const rating = form.querySelector('input[name="rating"]:checked');
        if (!rating) {
            if (typeof showNotification === 'function') {
                showNotification('Please select a rating', 'error');
            } else {
                alert('Please select a rating');
            }
            return;
        }

        const formData = new FormData(form);
        formData.append('action', 'submit_review'); // Required by review_handler.php

        const submitBtn = form.querySelector('.btn-submit-review');
        const originalBtnText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Posting...';

        fetch('review_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof showNotification === 'function') {
                    showNotification('Thank you! Your review has been submitted.', 'success');
                } else {
                    alert('Thank you! Your review has been submitted.');
                }
                form.reset();
                toggleReviewForm();
                loadReviews();
            } else {
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Error submitting review.', 'error');
                } else {
                    alert(data.message || 'Error submitting review.');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showNotification === 'function') {
                showNotification('Something went wrong. Please try again.', 'error');
            } else {
                alert('Something went wrong. Please try again.');
            }
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
}

function loadReviews() {
    const reviewsList = document.getElementById('reviewsList');
    if (!reviewsList) return;

    // Get product slug from URL
    const urlParams = new URLSearchParams(window.location.search);
    const pid = urlParams.get('product') || 'mens-polo-uniform';

    fetch(`review_handler.php?action=get_reviews&product_id=${pid}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderReviewStats(data.stats);
                renderReviews(data.reviews);
                renderBuyerFitInfo(data.stats, data.reviews);
            } else {
                reviewsList.innerHTML = '<p class="text-center text-muted">No reviews yet. Be the first to share your experience!</p>';
                const header = document.getElementById('aggregateReviewHeader');
                if (header) header.style.display = 'none';
                renderBuyerFitInfo(null, []);
            }
        })
        .catch(error => {
            console.error('Error loading reviews:', error);
            reviewsList.innerHTML = '<p class="text-center text-danger">Failed to load reviews.</p>';
        });
}

function renderReviewStats(stats) {
    const header = document.getElementById('aggregateReviewHeader');
    if (!header || !stats) return;

    header.style.display = 'block';

    const avgRatingBox = document.getElementById('avgRatingNum');
    const avgRatingStars = document.getElementById('avgRatingStars');
    
    if (avgRatingBox) avgRatingBox.textContent = stats.avg_rating;
    if (avgRatingStars) avgRatingStars.innerHTML = generateStars(stats.avg_rating);

    if (stats.total > 0) {
        updateFitBar('fitSmall', Math.round((stats.fit['Small'] / stats.total) * 100));
        updateFitBar('fitTrue', Math.round((stats.fit['True to Size'] / stats.total) * 100));
        updateFitBar('fitLarge', Math.round((stats.fit['Large'] / stats.total) * 100));
    }
}

function updateFitBar(idPrefix, pct) {
    const bar = document.getElementById(idPrefix + 'Bar');
    const label = document.getElementById(idPrefix + 'Pct');
    if (bar) bar.style.width = pct + '%';
    if (label) label.textContent = pct + '%';
}

function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalf = (rating - fullStars) >= 0.5;
    let html = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) html += '<i class="bi bi-star-fill text-gold"></i>';
        else if (i === fullStars + 1 && hasHalf) html += '<i class="bi bi-star-half text-gold"></i>';
        else html += '<i class="bi bi-star text-gold"></i>';
    }
    return html;
}

function renderReviews(reviews) {
    const reviewsList = document.getElementById('reviewsList');
    if (reviews.length === 0) {
        reviewsList.innerHTML = '<p class="text-center text-muted">No reviews yet. Be the first to share your experience!</p>';
        return;
    }

    let html = '';
    reviews.forEach(review => {
        const stars = generateStars(review.rating);
        const initials = getInitials(review.customerName);
        const isVerified = review.orderID && parseInt(review.orderID) > 0;
        
        const imageHtml = review.imagePath ? `
            <div class="review-image-wrapper">
                <img src="${review.imagePath}" alt="Customer photo" onclick="openImageModal('${review.imagePath}')" style="cursor: pointer;" />
            </div>
        ` : '';

        html += `
            <div class="review-card">
                <div class="review-card-header">
                    <div class="reviewer-avatar-wrapper">
                        <div class="reviewer-avatar">${initials}</div>
                    </div>
                    <div class="reviewer-info">
                        <div class="reviewer-name-group">
                            <span class="reviewer-name">${maskName(review.customerName)}</span>
                        </div>
                        <div class="reviewer-meta">
                            <div class="review-stars small">${stars}</div>
                            <span class="review-date">${formatDate(review.dateCreated)}</span>
                        </div>
                    </div>
                </div>
                <div class="review-fit-tag">
                    <span>Fit: <strong>${review.fit || 'True to Size'}</strong></span>
                    ${review.size ? `<span class="ms-2 ps-2 border-start">Size: <strong>${review.size}</strong></span>` : ''}
                </div>
                <div class="review-comment">
                    ${review.comment}
                </div>
                ${imageHtml}
                <div class="review-footer">
                    <button class="btn-like-review" data-id="${review.reviewID}" onclick="likeReview(this, ${review.reviewID})">
                        <i class="bi bi-hand-thumbs-up"></i> Helpful (${review.likes || 0})
                    </button>
                </div>
            </div>
        `;
    });
    reviewsList.innerHTML = html;
}

function getInitials(name) {
    if (!name || name === 'Customer') return 'C';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function likeReview(btn, reviewId) {
    if (btn.classList.contains('liked')) return;
    const formData = new FormData();
    formData.append('action', 'like_review');
    formData.append('review_id', reviewId);

    fetch('review_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.classList.add('liked');
            btn.innerHTML = `<i class="bi bi-hand-thumbs-up-fill"></i> Helpful (${parseInt(btn.textContent.match(/\d+/)[0]) + 1})`;
        }
    });
}

function maskName(name) {
    if (!name) return 'Customer';
    if (name.length <= 2) return name;
    return name.charAt(0) + '***' + name.slice(-1);
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
}

function openImageModal(imageSrc) {
    let modal = document.getElementById('imageModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageModal';
        modal.className = 'image-modal';
        modal.innerHTML = `
            <div class="image-modal-content">
                <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
                <img class="image-modal-img" id="modalImage" src="" alt="Review image">
            </div>
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeImageModal(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.style.display === 'flex') closeImageModal(); });
    }
    document.getElementById('modalImage').src = imageSrc;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function renderBuyerFitInfo(stats, reviews) {
    const summaryContainer = document.querySelector('.fit-summary-row');
    const tableBody = document.querySelector('.fit-data-table tbody');
    if (!summaryContainer || !tableBody) return;

    if (stats && stats.total > 0) {
        const smallPct = Math.round((stats.fit['Small'] / stats.total) * 100);
        const truePct = Math.round((stats.fit['True to Size'] / stats.total) * 100);
        const largePct = Math.round((stats.fit['Large'] / stats.total) * 100);
        summaryContainer.innerHTML = `
            <div class="me-3">Small: ${smallPct}%</div>
            <div class="me-3 fw-bold text-dark" style="text-decoration: underline; text-decoration-color: #000; text-underline-offset: 4px; text-decoration-thickness: 2px;">True to Size: ${truePct}%</div>
            <div>Large: ${largePct}%</div>
        `;
    } else {
        summaryContainer.innerHTML = `<div class="me-3">Small: N/A</div><div class="me-3 fw-bold text-dark">True to Size: N/A</div><div>Large: N/A</div>`;
    }

    let rowsHtml = '';
    const reviewsToShow = reviews ? reviews.slice(0, 5) : [];
    if (reviewsToShow.length === 0) {
        rowsHtml = '<tr><td colspan="5" class="text-center text-muted py-3">No reviews available yet.</td></tr>';
    } else {
        reviewsToShow.forEach(review => {
            rowsHtml += `
                <tr>
                    <td class="py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle text-muted"></i> ${maskName(review.customerName)}
                        </div>
                    </td>
                    <td><div class="text-gold" style="font-size:0.8rem">${generateStars(review.rating)}</div></td>
                    <td>
                        <div class="fw-bold">${review.fit || 'True to Size'}</div>
                        ${review.size ? `<small class="text-muted">Size: ${review.size}</small>` : ''}
                    </td>
                    <td><small class="text-muted" style="display:block; max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${review.comment}</small></td>
                    <td><small class="text-muted">${formatDate(review.dateCreated)}</small></td>
                </tr>
            `;
        });
    }
    tableBody.innerHTML = rowsHtml;
}

/* === Review Policy Modal === */
function openReviewPolicy() {
    const modal = document.getElementById('reviewPolicyModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Close on outside click
        modal.onclick = function(event) {
            if (event.target == modal) {
                closeReviewPolicy();
            }
        }
    }
}

function closeReviewPolicy() {
    const modal = document.getElementById('reviewPolicyModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}
