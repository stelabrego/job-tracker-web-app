"use strict";
var la = {}

la.init = function () {
	Util.addLis(Util.getEl('#login')[0], 'click', la.login);
	// Begin my custom code
	var data = {};
	data.flag = "getHours";
	data = JSON.stringify(data);
	// send ajax request to server with custom flag that will be directed to home.php
	Util.sendRequest('../xhr/routes.php', function (res) {
		var response = JSON.parse(res.responseText);
		var chartSections = response.chartSections;
		console.log(chartSections);
		var labels = chartSections.map(function (section) {
			return section.label;
		});
		var data = chartSections.map(function (section) {
			return section.hours;
		});

		var ctx = document.getElementById("graph");
		var myChart = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					data: data,
					backgroundColor: [
						'rgba(255, 99, 132, 0.2)',
						'rgba(54, 162, 235, 0.2)',
						'rgba(255, 206, 86, 0.2)',
						'rgba(75, 192, 192, 0.2)',
						'rgba(153, 102, 255, 0.2)',
						'rgba(255, 159, 64, 0.2)'
					],
					borderColor: [
						'rgba(255,99,132,1)',
						'rgba(54, 162, 235, 1)',
						'rgba(255, 206, 86, 1)',
						'rgba(75, 192, 192, 1)',
						'rgba(153, 102, 255, 1)',
						'rgba(255, 159, 64, 1)'
					],
					borderWidth: 1
				}]
			},
			options: {
				legend: {
					display: false
				},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero: true
						}
					}]
				}
			}
		});
	}, data);
	// end my custom code

}

/* CHECKS THAT THE USERNAME AND PASSWORD ARE NOT BLANK FIRST */
la.login = function (e) {
	var data = {}
	data.email = Util.getEl('#email')[0].value;
	data.password = Util.getEl('#password')[0].value;
	if (data.email == "" || data.password == "") {
		Util.msgBox({
			heading: { text: 'ERROR', background: 'red' },
			body: { text: 'Email and Password cannot be blank' },
			rightbtn: { text: 'Okay', background: 'green', display: 'block' }
		})
		Util.addLis(Util.getEl('#rightbtn')[0], 'click', function () {
			Util.closeMsgBox();
		});

	}

	/* IF THE ADDADMIN BUTTON IS CLICKED THEN ADD AN ADMIN OTHERWISE LOGIN */
	else {
		Util.msgBox({
			heading: { text: 'PROCESSING LOGIN', background: 'green' },
			body: { text: 'We are processing your login please wait...' }
		});
		data.flag = 'login';
		data = JSON.stringify(data);
		Util.sendRequest('xhr/routes.php', function (res) {
			Util.closeMsgBox();
			var response = JSON.parse(res.responseText);
			if (response.masterstatus === "error") {
				Util.msgBox({
					heading: { text: 'ERROR', background: 'red' },
					body: { text: response.msg }
				})

				setTimeout(function () {
					Util.closeMsgBox();
				}, 3000);
			}
			else {
				/* YOU WILL NEED TO CHANGE THIS URL TO YOUR URL FOR YOUR HOME PAGE OF YOUR JOB TRACKER APPLICATION DO NOT INCLUDE THE ANGLE BRACKETS */
				window.location = "http://cps276.stelabr.com/job-tracker/home/";
			}
		}, data);
	}
}

la.init();
