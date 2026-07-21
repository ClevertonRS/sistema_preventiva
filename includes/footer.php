<footer></footer>
<script>
	// small helper: focus first invalid input on load if present
	document.addEventListener('DOMContentLoaded', function(){
		var el = document.querySelector('input:invalid');
		if(el) el.focus();
	});
</script>
</body>
</html>
