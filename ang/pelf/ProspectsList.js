(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfProspectsList', ['crmApi', '$timeout', function(crmApi, $timeout) {
    return {
      scope: {
        pelf: '=',
        prospects: '='
      },
      controller: ['$scope', function ($scope) {
        console.log("pelfProspectsList ", $scope.pelf);
        $scope.crmUrl = CRM.url;
        _.forEach($scope.prospects, function(activity) { $scope.pelf.fundingCalcs(activity); });
      }], // end of controller.
      link: function(scope, elem, attrs) { },
      templateUrl: '~/pelf/ProspectsList.html',
    };
  }]);

})(angular, CRM.$, CRM._);

