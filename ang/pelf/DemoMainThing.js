(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('demoMainThing', function() {
    return {
      scope: {
        testthing: '='
      },
      controller: ['$scope', function ($scope) {
        console.log("main thing controller", $scope.testthing);
        $scope.testthing.push('new thing');
      }],
      template: 'demoMainThing: {{ testthing.length }}, <pre ng-click="testthing[0]=\'foo\'">{{ testthing | json }}</pre>'
    };
  });

})(angular, CRM.$, CRM._);

