(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('demoSubThing', function() {
    return {
      scope: {
        sharedMethod  : '=',
        name  : '='
      },
      controller: ['$scope', function ($scope) {
        console.log("demoSubThing controller", $scope);
        $scope.sharedMethod = function() {
          console.log("sharedMethod called");
          $scope.name = "Foo";
        };
      }],
      template: '<p>This is the sub directive name: "{{name}}"</p>'
    };
  });

})(angular, CRM.$, CRM._);

