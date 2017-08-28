(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfContractsList', ['crmApi', '$timeout', function(crmApi, $timeout, pelf) {
    return {
      scope: {
        pelf: '='
      },
      controller: ['$scope', '$location', 'crmApi', function ($scope, $location, crmApi) {
        $scope.crmUrl = CRM.url;
        // We need to load our data.
        crmApi('activity', 'GetPelfContract', [])
        .then(function(result) {
          console.log("contracts loaded:", result);
          $scope.contracts = result.values;
        });
      }], // end of controller.
      link: function(scope, elem, attrs) {
      },
      templateUrl: '~/pelf/ContractsList.html',
    };
  }]);

})(angular, CRM.$, CRM._);

