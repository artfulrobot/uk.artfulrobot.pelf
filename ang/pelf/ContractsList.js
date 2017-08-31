(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfContractsList', ['crmApi', '$timeout', function(crmApi, $timeout, pelf) {
    return {
      scope: {
        pelf: '=',
        contracts: '='
      },
      controller: ['$scope', function ($scope) {
        $scope.crmUrl = CRM.url;
        // get sumFunding setup.
        _.forEach($scope.contracts, function(contract) {
          $scope.pelf.fundingCalcs(contract);
        });
      }], // end of controller.
      link: function(scope, elem, attrs) { },
      templateUrl: '~/pelf/ContractsList.html',
    };
  }]);

})(angular, CRM.$, CRM._);

