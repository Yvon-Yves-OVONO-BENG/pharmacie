$(document).ready(function () {
    $(document).off('change', '.retirer-toggle');

	$(document).on('change', '.retirer-toggle', function () {
        console.log("Checkbox changée une seulle fois");
        
		let = checkbox = $(this);
		let produitId = checkbox.data('id');
		const url =checkbox.data('url');
		
		
		$.ajax({
			url: url,
			method: 'POST',
			data: {
				id: produitId
			},
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
			success: function (response) {
				if (response.success) {
					//je désactive temporairement le checkbox
					checkbox.prop('disabled', response.retirer);

					Swal.fire({
						toast: true,
						position: 'top-end',
						icon: response.retirer ? 'success' : 'info',
						title: response.retirer ?
							'Produit retiré des étagères !'
							: 'Produit remis en vente !',
						showConfirmButton: false,
						timer: 5000,
						timerProgressBar: true
					});
				} else {
					Swal.fire({
						toast: true,
						position: 'top-start',
						icon: 'error',
						title: 'Echec de la mise à jour',
						showConfirmButton: false,
						timer: 5000,
						timerProgressBar: true
					});
					checkbox.prop('checked', !checked.prop('checked'));
				}
			},
			error: function () {
				Swal.fire({
					toast: true,
					position: 'top-end',
					icon: 'error',
					title: 'Erreur de communication avec le serveur !',
					showConfirmButton: false,
					timer: 5000,
					timerProgressBar: true
				});

				//rollback si Erreur
				checkbox.prop('checked', !checkbox.prop('checked'));
			},
			complete: function () {
				checkbox.prop('disabled', false);
			}
		});
	});
});