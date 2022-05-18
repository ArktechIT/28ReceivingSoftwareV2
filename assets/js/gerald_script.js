const setCurrentContainer = () => {
	const currentBucket = getCurrentContainer()
	
	$("#containerId").text(currentBucket)
}

const getCurrentContainer = () => {
	return (localStorage.getItem('currentBucket')) ? localStorage.getItem('currentBucket') : '';
}

$("#editContainer").on('click',function(){

	Swal.fire({
		title: '',
		html:
			/*html*/`
			<div class="form-group">
				<label for="bucket">Bucket:&nbsp;&nbsp;&nbsp;</label>
				<input type="text" list="bucketList" id="bucket" name="bucket" value="${getCurrentContainer()}" class="swal2-input" placeholder="Bucket" autofocus=on>
			</div>
			<datalist id="bucketList">${getBucketDataList()}</datalist>
			</div>
		`,
		showCancelButton: true,
		cancelButtonText: 'Remove Bucket',
		confirmButtonText: 'OK',
		confirmButtonColor: '#4a69bd',
		showCloseButton: true,
		focusConfirm: false,
		allowOutsideClick: false,
		didOpen: () => {
			const bucketEl = Swal.getPopup().querySelector('#bucket')
			bucketEl.addEventListener('keypress',function(e){
				if(e.which==13) Swal.clickConfirm()
			});	
		},
		preConfirm: () => {
			const bucketEl = Swal.getPopup().querySelector('#bucket')
			const bucket = bucketEl.value;
			
			var objBucket = $('#bucketList').find(
				"option[value='" + bucket + "']"
			);

			if (objBucket.length == 0) {
				Swal.showValidationMessage(`INVALID BUCKET`);
			}

			if (!bucket) {
				Swal.showValidationMessage(`PLEASE INPUT BUCKET`);
			}
			bucketVal = bucket;

			return {bucket}
		},
	}).then((result) => {
		const bucketValue = (result.isConfirmed) ? result.value.bucket : ''
		localStorage.setItem('currentBucket',bucketValue)
		setCurrentContainer()
		$("#itemTags").focus()
	});	
})