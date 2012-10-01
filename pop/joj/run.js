#!/usr/bin/env node
var Joj = require('./Packages/Framework/Joj/Classes/Joj'),
	runtime;

runtime = new Joj.Runtime();
runtime.run();