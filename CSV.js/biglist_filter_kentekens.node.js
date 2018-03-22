#!/usr/bin/env node

const hrStartTime = process.hrtime()

let csv = require('fast-csv')
let fs = require('fs')
let path = require('path')

let inputfile = '../Open_Data_RDW__Gekentekende_voertuigen.csv'
let outputPath = '../Biglist_Outputs/'

let outputFiles = []
let outputCSVs = []

let headers = []


function headerIndex(data) {
	headers = data
	let len = data.length;

	let headerIndex = fs.createWriteStream(path.join(outputPath,'index.csv'))
	for (var i = 0; i < len; i++) {
		headerIndex.write(''+i+','+data[i]+"\n")
		outputFiles[i] = fs.createWriteStream(path.join(outputPath,''+i+'.csv'))
		//	outputCSVs[i] = csv.createWriteStream().pipe(outputFiles[i])
		//	outputFiles[i].write(data[0]+','+data[i]+"\n")
		let writerCallback = () => {
			let n = i
			return (err, data) => outputFiles[n].write(data+"\n")
		}
		csv.writeToString([[ data[0], data[i] ]], {}, writerCallback())
	}
	headerIndex.end()
}

function distributeOutputLine(data) {
	let len = data.length;
	for (var i = 0; i < len; i++) {
		// csv.writeToStream( outputFiles[i], [ data[0], data[i] ] )
		if(data[i] === '') continue
		if(data[i] === 'N.v.t.') continue
		let outLine = data[i]
		if(outLine.indexOf(',') >= 0)
			outLine = '"' + outLine.replace('"', '\\"') + '"'
		outputFiles[i].write(data[0]+','+outLine+"\n")
	}
}

function elapsedTime() {
	let hrend = process.hrtime(hrStartTime)

	return '' + (hrend[0] + (hrend[1] / 1000000000)).toFixed(3)
	// console.info("Execution time (hr): %ds %dms", hrend[0], hrend[1]/1000000);
}

function secondsToTime(s) {
	let h = Math.floor(s / 3600)
	s -= h * 3600
	let m = Math.floor(s / 60)
	s -= m * 60
	s = Math.floor(s)
	h = String(h)
	m = String(m)
	s = String(s)
	return h + ':' + m.padStart(2, '0') + ':' + s.padStart(2, '0');
}

function timeRemaining(percent) {
	let elapsed = elapsedTime()
	let remaining = elapsed / percent * (100 - percent)
	return remaining
}

function totalTime(percent) {
	let elapsed = 1 * elapsedTime()
	return timeRemaining(percent) + elapsed
}

let inputSize = fs.statSync(inputfile).size
let readStream = fs.createReadStream(inputfile, {start: 0})
let row = 0
let csvStream = csv()
	.on('data', data => {
		if(row === 0) headerIndex(data)
		else {distributeOutputLine(data)}
		if(row % 1000 === 0) {
			let percent_complete = (readStream.pos / inputSize) * 100
			process.stdout.write("\r" + readStream.pos + ' / ' + inputSize + ' (' + percent_complete.toFixed(2) + '%) ')
			process.stdout.write(' ' + secondsToTime(elapsedTime()) + ' ' + secondsToTime(timeRemaining(percent_complete)))
			process.stdout.write(' ' + secondsToTime(totalTime(percent_complete)))
		}
		if(row > 100000) {
			console.log()
			process.exit()
		}
		row++
	})
	.on('end', _=> console.log('done'))

readStream.pipe(csvStream)
