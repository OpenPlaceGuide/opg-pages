describe('Pages Test', () => {
    it('Shows a Subcity Page', () => {
        cy.visit('/bole/');
        cy.contains('Bole');
        cy.screenshot();

    });
    it('Shows a Banks Page', () => {
        cy.visit('/bole/banks');
        cy.contains('Banks in Bole Subcity');
        cy.screenshot();
    });
    it('Shows a Businesses Page', () => {
        cy.visit('/nefas-silk/businesses');
        cy.contains('Businesses');
        cy.screenshot();
    });
    it('Shows a Place Page', () => {
        cy.visit('/bandira/');
        cy.contains('Bandira');
        cy.screenshot();
    });
    it('Shows another Place Page', () => {
        cy.visit('/zemen-bank/');
        cy.contains('Zemen');
        cy.screenshot();
    });
});
