/*
 * @license
 */

var key,
	Joj		= require('./ValueObject');
Joj.Runtime	= require('./Runtime').Runtime;

// Export each class in Joj
for (key in Joj) {
	exports[key] = Joj[key];
}