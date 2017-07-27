(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('demoMainThing', function() {
    return {
      controller: ['$scope', function ($scope) {
        console.log("main thing controller", $scope);
        $scope.name = "Bar";
        $scope.callSharedMethod = function() {
          console.log("callSharedMethod");
          $scope.sharedMethod();
        };
      }],
      template: '<p>Main directive</p><demo-sub-thing shared-method="sharedMethod" name="name" ></demo-sub-thing><a href ng-click="callSharedMethod()" >Click me</a>'
    };
  });

})(angular, CRM.$, CRM._);

