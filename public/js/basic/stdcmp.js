JudgeBoy.namespace('JudgeBoy.view.Basic');

JudgeBoy.view.Basic = {
	stdcmp: function () {
		var buts, i;
		buts = document.getElementsByClassName('UP_DOWN_IMG');
		for (i = 0; i < buts.length; i += 1){
			buts[i].addEventListener('click', function(e) {
				var ele, stu, sol;
				ele = e.srcElement;
				stu = ele.parentNode.children[1].children[1];
				sol = ele.parentNode.children[1].children[0];
				if (ele.src.includes('attention')) {
					ele.src = 'http://45.32.107.147:83/svg/undo.svg';
					sol.style.display = 'block';
				}
				else{
					ele.src = 'http://45.32.107.147:83/svg/attention.svg';
					sol.style.display = 'none';
				}
			});
		}
	},
	
	candi_match: function () {
		var buts, i;
		buts = document.getElementsByClassName('CANDI_INPUT');
		for (i = 0; i < buts.length; i += 1){
			buts[i].addEventListener('click', function(e) {
				var ele, sol_src, id, sol_blks, j;
				ele = e.srcElement;
				sol_src = ele.value;
				id = ele.id;
				sol_blks = document.getElementsByClassName('SOL_DIV');
				for (j = 0; j < sol_blks.length; j += 1) {
					if (sol_blks[j].id === id) {
						sol_blks[j].children[0].innerHTML = sol_src;
						sol_blks[j].style.display = 'block';
					}
					break;
				}
			});
		}
	}

};

