import { Component, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { DatePipe } from '@angular/common';

interface Machine {
  id: number;
  name: string;
}

interface Client {
  id: number;
  name: string;
}

interface Reservation {
  id: number;
  machine: Machine;
  client: Client;
  starts_at: string;
  ends_at: string;
  status: string;
}

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [FormsModule, DatePipe],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {
  reservations = signal<Reservation[]>([]);
  reservationsLoading = signal(false);
  reservationsError = signal('');

  filterMachineId: number | null = null;
  filterStatus = '';
  private http = inject(HttpClient);

  machines = signal<Machine[]>([]);
  clients = signal<Client[]>([]);
  errorMessage = signal('');

  machineId: number | null = null;
  clientId: number | null = null;
  startsAt = '';
  endsAt = '';

  saving = signal(false);
  saveError = signal('');
  saveSuccess = signal('');
  updatingStatus = signal<number | null>(null);
  statusError = signal('');
  statusSuccess = signal('');

  ngOnInit() {
    this.http.get<Machine[]>('http://127.0.0.1:8000/api/machines').subscribe({
      next: (data) => this.machines.set(data),
      error: () => this.errorMessage.set('Não foi possível buscar as máquinas.'),
    });

    this.http.get<Client[]>('http://127.0.0.1:8000/api/clients').subscribe({
      next: (data) => this.clients.set(data),
      error: () => this.errorMessage.set('Não foi possível buscar os clientes.'),
    });
    this.loadReservations();
  }

  createReservation() {
    if (this.saving()) {
      return;
    }

    this.saveError.set('');
    this.saveSuccess.set('');

    if (this.machineId === null || this.clientId === null || !this.startsAt || !this.endsAt) {
      this.saveError.set('Preencha todos os campos.');
      return;
    }

    const start = new Date(this.startsAt);
    const end = new Date(this.endsAt);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
      this.saveError.set('Informe datas válidas.');
      return;
    }

    this.saving.set(true);

    this.http
      .post<{ id: number }>(
        'http://127.0.0.1:8000/api/reservations',
        {
          machine_id: this.machineId,
          client_id: this.clientId,
          starts_at: start.toISOString(),
          ends_at: end.toISOString(),
        },
        {
          headers: { Accept: 'application/json' },
        },
      )
      .subscribe({
        next: (reservation) => {
          this.saveSuccess.set(`Reserva #${reservation.id} criada como pendente.`);
          this.saving.set(false);
          this.loadReservations();
        },
        error: (error) => {
          this.saveError.set(
            error.error?.message || 'Não foi possível cadastrar. Verifique a API.',
          );
          this.saving.set(false);
        },
      });
  }

  updateStatus(id: number, status: string) {
    if (this.updatingStatus() !== null) {
      return;
    }

    this.updatingStatus.set(id);
    this.statusError.set('');
    this.statusSuccess.set('');

    this.http
      .patch(
        `http://127.0.0.1:8000/api/reservations/${id}/status`,
        { status },
        { headers: { Accept: 'application/json' } },
      )
      .subscribe({
        next: () => {
          this.statusSuccess.set(`Status da reserva #${id} atualizado.`);
          this.updatingStatus.set(null);
          this.loadReservations();
        },
        error: (error) => {
          this.statusError.set(
            error.error?.message || 'Não foi possível atualizar o status. Verifique a API.',
          );
          this.updatingStatus.set(null);
        },
      });
  }

  loadReservations() {
    const params: { machine_id?: number; status?: string } = {};

    if (this.filterMachineId !== null) {
      params.machine_id = this.filterMachineId;
    }

    if (this.filterStatus !== '') {
      params.status = this.filterStatus;
    }

    this.reservationsLoading.set(true);
    this.reservationsError.set('');

    this.http
      .get<Reservation[]>('http://127.0.0.1:8000/api/reservations', {
        params,
        headers: { Accept: 'application/json' },
      })
      .subscribe({
        next: (data) => {
          this.reservations.set(data);
          this.reservationsLoading.set(false);
        },
        error: (error) => {
          this.reservationsError.set(
            error.error?.message || 'Não foi possível carregar as reservas.',
          );
          this.reservationsLoading.set(false);
        },
      });
  }
}
