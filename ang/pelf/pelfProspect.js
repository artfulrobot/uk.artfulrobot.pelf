(function(angular, $, _) {
  // "pelfProspect" is a basic skeletal directive.
  // Example usage: <div pelf-prospect="{foo: 1, bar: 2}"></div>
  angular.module('pelf').directive('pelfProspect', function() {
    return {
      restrict: 'E',
      templateUrl: '~/pelf/pelfProspect.html',
      scope: {
        prospectId: '=',
        pelfProspect: '='
      },
      link: function($scope, $el, $attr) {
        var ts = $scope.ts = CRM.ts('pelf');

        $scope.$watch('prospectId', function(newValue){
          $scope.prospectId = newValue;
        });

        // ?
        $scope.$watch('pelfProspect', function(newValue){
          $scope.myOptions = newValue;
        });

      }
    };
  });
})(angular, CRM.$, CRM._);
