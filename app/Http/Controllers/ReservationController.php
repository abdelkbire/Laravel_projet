<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voyage;
use App\Models\Reservation;
use App\Models\Ville;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReservationController extends Controller
{
    public function create($id)
    {
        $voyage = Voyage::findOrFail($id);
        return view('travel.reserve', compact('voyage'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'required|string|max:15',
            'voyage_id' => 'required|exists:voyages,id',
        ]);
    
        // Création de la réservation
        $reservation = Reservation::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'voyage_id' => $request->voyage_id,
        ]);
    
        // Redirection vers la vue du ticket avec succès
        return redirect()->route('pdf.ticket', $reservation->id)->with('success', 'Réservation effectuée avec succès.');
    }
    
    public function showTicket($id)
    {
        $reservation = Reservation::with('voyage')->findOrFail($id);
    
        // Génération du PDF
        $pdf = $this->generatePDF($reservation);
    
        return $pdf->stream('ticket.pdf');
    }
    
    private function generatePDF($reservation)
    {
        // Data to pass to the view
        $data = [
            'reservation' => $reservation,
        ];
    
        // Generate PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('pdf.ticket', $data));
    
        // (Optional) Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');
    
        // Render PDF (important for rendering before output)
        $pdf->render();
    
        return $pdf;
    }
    public function dashboard()
    {
        $monthlyEarnings = Reservation::join('voyages', 'reservations.voyage_id', '=', 'voyages.id')->sum('voyages.prix');
        $annualEarnings = Reservation::count();
        $totalTasks = Voyage::count();
        $totalCities = Ville::count();

        return view('dashboard', compact('monthlyEarnings', 'annualEarnings', 'totalTasks', 'totalCities'));
    }
    

}
