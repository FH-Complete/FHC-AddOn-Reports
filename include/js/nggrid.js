var app = angular.module("ngApp", ["ngGrid"]);
	app.controller("ngCtrl", function($scope) {
		$scope.ngData = data;
		$scope.gridOptions = { 
			data: "ngData",
			plugins: [new ngGridCsvExportPlugin()],
			showFooter: true,
			showGroupPanel: true,
			enableColumnReordering: true,
			enableColumnResize: true,
			showColumnMenu: true,
			showFilter: true
		};
	});	
