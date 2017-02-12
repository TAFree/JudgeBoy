var JudgeBoy = JudgeBoy || {};

JudgeBoy.namespace = function (ns_string) {
	
	var parts = ns_string.split('.'),
	    father = JudgeBoy,
	    i;
	
	if (parts[0] === 'JudgeBoy') {
		parts = parts.slice(1);
	}
	
	for (i = 0; i < parts.length; i += 1) {
		// Create property if it doesn't exist
		if (typeof father[parts[i]] === 'undefined') {
			father[parts[i]] = {};
		}
		father = father[parts[i]];
	}
	
	return father;

};
