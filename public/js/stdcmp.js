var buts, i;

buts = document.getElementsByClassName('UP_DOWN_IMG');
for (i = 0; i < buts.length; i += 1){
buts[i].addEventListener('click', function(e) {
	var ele, stu, sol;
	ele = e.srcElement;
	stu = ele.parentNode.children[1].children[1];
	sol = ele.parentNode.children[1].children[0];
	if (ele.src.includes('attention')) {
		ele.src = '../svg/undo.svg';
		sol.style.display = 'block';
	}
	else{
		ele.src = '../svg/attention.svg';
		sol.style.display = 'none';

	}
});
}

